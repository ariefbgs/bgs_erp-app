<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\SupplierInvoiceInputService;
use App\Services\InvoicePaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupplierInvoiceInputTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','session.driver'=>'array']);
        DB::purge('sqlite');
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        // Current access checks resolve the user's optional settings relation.
        $accessMigration = require base_path('database/migrations/2026_10_13_010000_create_user_access_settings_table.php');
        $accessMigration->up();
        $this->assertSame(':memory:',config('database.connections.sqlite.database'));
        // Shared sidebar queries deployment permissions while rendering real views.
        Schema::create('permissions',function(Blueprint $t){$t->id();$t->string('name');});
        Schema::create('role_permissions',function(Blueprint $t){$t->id();$t->string('role');$t->unsignedBigInteger('permission_id');});
        Schema::create('suppliers',function(Blueprint $t){$t->id();$t->string('name');});
        Schema::create('products',function(Blueprint $t){$t->id();$t->string('name');$t->string('product_code');});
        Schema::create('po_suppliers',function(Blueprint $t){$t->id();$t->unsignedBigInteger('supplier_id');$t->string('po_supplier_number');$t->string('status');foreach(['subtotal','discount_amount','tax_amount','total'] as $c)$t->decimal($c,15,2);});
        Schema::create('po_supplier_details',function(Blueprint $t){$t->id();$t->unsignedBigInteger('po_supplier_id');$t->unsignedBigInteger('product_id');$t->integer('quantity');$t->decimal('purchase_price',15,2);$t->decimal('subtotal',15,2);});
        Schema::create('invoice_suppliers',function(Blueprint $t){$t->id();$t->string('invoice_number')->unique();$t->unsignedBigInteger('po_supplier_id');$t->date('invoice_date');$t->date('due_date')->nullable();$t->string('status');foreach(['amount','tax','total'] as $c)$t->decimal($c,15,2);$t->timestamps();});
        Schema::create('invoice_supplier_details',function(Blueprint $t){$t->id();$t->unsignedBigInteger('invoice_supplier_id');$t->unsignedBigInteger('product_id');$t->integer('quantity');$t->decimal('unit_price',15,2);$t->decimal('subtotal',15,2);$t->timestamps();});
        $migration=require base_path('database/migrations/2026_10_12_120000_create_invoice_payment_confirmations_table.php');$migration->up();
        DB::table('suppliers')->insert(['id'=>1,'name'=>'UAT Supplier']);
        DB::table('products')->insert(['id'=>1,'name'=>'UAT Product','product_code'=>'UAT-P']);
        DB::table('po_suppliers')->insert(['id'=>1,'supplier_id'=>1,'po_supplier_number'=>'UAT-PO','status'=>'sent','subtotal'=>200000,'discount_amount'=>20000,'tax_amount'=>19800,'total'=>199800]);
        DB::table('po_supplier_details')->insert(['po_supplier_id'=>1,'product_id'=>1,'quantity'=>2,'purchase_price'=>100000,'subtotal'=>200000]);
    }
    private function data(string $number='UAT-SUP-INV'): array
    {
        return ['po_supplier_id'=>1,'invoice_number'=>$number,'invoice_date'=>'2026-10-12','due_date'=>'2026-10-31','confirmed'=>'1'];
    }
    public function test_creates_unpaid_invoice_with_server_values_and_product_details(): void
    {
        $data=$this->data();$data['total']=1;$data['status']='paid';
        $id=app(SupplierInvoiceInputService::class)->create($data);
        $i=DB::table('invoice_suppliers')->find($id);
        $this->assertSame('unpaid',$i->status);$this->assertEquals(180000,$i->amount);$this->assertEquals(19800,$i->tax);$this->assertEquals(199800,$i->total);
        $d=DB::table('invoice_supplier_details')->first();$this->assertSame(1,(int)$d->product_id);$this->assertSame(2,(int)$d->quantity);$this->assertEquals(100000,$d->unit_price);
    }
    public function test_duplicate_po_rejected(): void
    {
        app(SupplierInvoiceInputService::class)->create($this->data());
        try { app(SupplierInvoiceInputService::class)->create($this->data('OTHER'));$this->fail('Duplicate PO accepted'); }
        catch(ValidationException $e){$this->assertSame(1,DB::table('invoice_suppliers')->count());}
    }
    public function test_draft_cancelled_and_inconsistent_po_rejected(): void
    {
        foreach(['draft','cancelled'] as $status){
            DB::table('po_suppliers')->where('id',1)->update(['status'=>$status]);
            try{app(SupplierInvoiceInputService::class)->create($this->data());$this->fail('Invalid status accepted');}
            catch(ValidationException $e){$this->assertSame(0,DB::table('invoice_suppliers')->count());}
        }
        DB::table('po_suppliers')->where('id',1)->update(['status'=>'sent','total'=>1]);
        $this->expectException(ValidationException::class);app(SupplierInvoiceInputService::class)->create($this->data());
    }
    public function test_detail_write_failure_rolls_back_header(): void
    {
        Schema::drop('invoice_supplier_details');
        try{app(SupplierInvoiceInputService::class)->create($this->data());$this->fail('Failure ignored');}
        catch(\Illuminate\Database\QueryException $e){$this->assertSame(0,DB::table('invoice_suppliers')->count());}
    }
    public function test_staff_cannot_create(): void
    {
        $u=new User();$u->id=1;$u->role='staff';
        $this->actingAs($u)->get(route('supplier-invoice-input.create'))->assertForbidden();
        $this->post(route('supplier-invoice-input.store'),$this->data())->assertForbidden();
        $this->assertSame(0,DB::table('invoice_suppliers')->count());
    }
    public function test_admin_create_show_and_payment_integration(): void
    {
        $u=new User();$u->id=1;$u->role='admin';
        $this->actingAs($u)->post(route('supplier-invoice-input.store'),$this->data())->assertRedirect(route('supplier-invoice-input.show',1));
        $this->get(route('supplier-invoice-input.show',1))->assertOk()->assertSee('UAT-SUP-INV')->assertSee('UAT Product');
        $this->get(route('invoice-payments.index','supplier'))->assertOk()->assertSee('UAT-SUP-INV');
        $this->post(route('invoice-payments.store','supplier'),['invoice_id'=>1,'payment_date'=>now()->format('Y-m-d'),'reference'=>'UAT-SUP-PAY','confirmed'=>1])->assertRedirect();
        $this->assertSame('paid',DB::table('invoice_suppliers')->find(1)->status);
        $this->assertSame('UAT-SUP-PAY',DB::table('invoice_payment_confirmations')->first()->reference);
    }
    public function test_invalid_due_date_and_missing_confirmation_rejected(): void
    {
        $u=new User();$u->id=1;$u->role='admin';$data=$this->data();$data['due_date']='2026-10-01';unset($data['confirmed']);
        $this->actingAs($u)->post(route('supplier-invoice-input.store'),$data)->assertSessionHasErrors(['due_date','confirmed']);
        $this->assertSame(0,DB::table('invoice_suppliers')->count());
    }
}
