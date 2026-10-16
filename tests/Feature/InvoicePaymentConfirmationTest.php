<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\InvoicePaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoicePaymentConfirmationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        // Current access checks resolve the user's optional settings relation.
        $accessMigration = require base_path('database/migrations/2026_10_13_010000_create_user_access_settings_table.php');
        $accessMigration->up();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach (['invoice_customers','invoice_suppliers'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('invoice_number');
                $table->string('status'); $table->string('payment_status')->nullable();
                $table->decimal('total',15,2); $table->timestamps();
            });
        }
        $migration = require base_path('database/migrations/2026_10_12_120000_create_invoice_payment_confirmations_table.php');
        $migration->up();
    }

    private function invoice(string $kind, string $status = 'partial'): int
    {
        return DB::table($kind === 'customer' ? 'invoice_customers' : 'invoice_suppliers')->insertGetId([
            'invoice_number' => 'UAT-'.$kind, 'status' => $kind === 'supplier' ? 'unpaid' : $status,
            'payment_status' => 'sent', 'total' => '222000.00',
        ]);
    }

    public function test_customer_payment_preserves_invoice_allocation_status_and_total(): void
    {
        $id = $this->invoice('customer');
        app(InvoicePaymentService::class)->markPaid('customer',$id,'2026-10-12',1,'UAT');
        $invoice = DB::table('invoice_customers')->find($id);
        $this->assertSame('paid',$invoice->payment_status);
        $this->assertSame('partial',$invoice->status);
        $this->assertEquals(222000,$invoice->total);
        $this->assertSame(1,DB::table('invoice_payment_confirmations')->count());
        $this->assertSame('UAT',DB::table('invoice_payment_confirmations')->first()->reference);
    }

    public function test_supplier_unpaid_and_overdue_can_be_confirmed(): void
    {
        foreach (['unpaid','overdue'] as $status) {
            $id = $this->invoice('supplier');
            DB::table('invoice_suppliers')->where('id',$id)->update(['status'=>$status]);
            app(InvoicePaymentService::class)->markPaid('supplier',$id,'2026-10-12',1);
            $this->assertSame('paid',DB::table('invoice_suppliers')->find($id)->status);
        }
        $this->assertSame(2,DB::table('invoice_payment_confirmations')->count());
    }

    public function test_duplicate_confirmation_is_rejected_without_second_record(): void
    {
        $id=$this->invoice('customer');
        $service=app(InvoicePaymentService::class);
        $service->markPaid('customer',$id,'2026-10-12',1);
        try { $service->markPaid('customer',$id,'2026-10-12',1); $this->fail('Duplicate accepted'); }
        catch (ValidationException $e) { $this->assertSame(1,DB::table('invoice_payment_confirmations')->count()); }
    }

    public function test_cancelled_and_missing_invoice_are_rejected(): void
    {
        $id=$this->invoice('customer','cancelled');
        foreach ([$id,99999] as $candidate) {
            try { app(InvoicePaymentService::class)->markPaid('customer',$candidate,'2026-10-12',1); $this->fail('Invalid accepted'); }
            catch (ValidationException $e) { $this->assertSame(0,DB::table('invoice_payment_confirmations')->count()); }
        }
    }

    public function test_audit_failure_does_not_change_invoice(): void
    {
        $id=$this->invoice('customer');
        Schema::drop('invoice_payment_confirmations');
        try { app(InvoicePaymentService::class)->markPaid('customer',$id,'2026-10-12',1); $this->fail('Audit failure ignored'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertSame('sent',DB::table('invoice_customers')->find($id)->payment_status); }
    }

    public function test_staff_cannot_confirm_payment(): void
    {
        $user=new User(); $user->id=1; $user->role='staff';
        $this->actingAs($user)->post(route('invoice-payments.store','customer'),[])->assertForbidden();
        $this->assertSame(0,DB::table('invoice_payment_confirmations')->count());
    }

    public function test_admin_requires_confirmation_and_valid_payment_date(): void
    {
        $user=new User(); $user->id=1; $user->role='admin';
        $this->actingAs($user)->post(route('invoice-payments.store','customer'),[
            'invoice_id'=>1,'payment_date'=>'2099-01-01',
        ])->assertSessionHasErrors(['confirmed','payment_date']);
    }
}
