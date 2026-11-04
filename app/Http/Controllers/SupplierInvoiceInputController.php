<?php
namespace App\Http\Controllers;

use App\Services\SupplierInvoiceInputService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceInputController extends Controller
{
    private function authorizeInput(Request $request): void
    {
        abort_unless($request->user() && $request->user()->canUseAdminFeatures(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeInput($request);
        $data=$request->validate(['search'=>'nullable|string|max:100','status'=>'nullable|in:unpaid,paid,overdue']);
        $q=DB::table('invoice_suppliers as i')->join('po_suppliers as po','po.id','=','i.po_supplier_id')
            ->join('suppliers as s','s.id','=','po.supplier_id');
        if (!empty($data['search'])) $q->where(function($query) use ($data) {
            $query->where('i.invoice_number','like','%'.$data['search'].'%')->orWhere('s.name','like','%'.$data['search'].'%')->orWhere('po.po_supplier_number','like','%'.$data['search'].'%');
        });
        if (!empty($data['status'])) $q->where('i.status',$data['status']);
        $invoices=$q->select('i.*','po.po_supplier_number','s.name as supplier_name')->orderByDesc('i.id')->paginate(8)->withQueryString();
        return view('supplier_invoice_input.index',compact('invoices'));
    }

    public function create(Request $request)
    {
        $this->authorizeInput($request);
        $pos=DB::table('po_suppliers as po')->join('suppliers as s','s.id','=','po.supplier_id')
            ->whereIn('po.status',['sent','confirmed','received'])
            ->whereNotExists(function($q) { $q->selectRaw('1')->from('invoice_suppliers as i')->whereColumn('i.po_supplier_id','po.id'); })
            ->select('po.*','s.name as supplier_name')->orderByDesc('po.id')->get();
        return view('supplier_invoice_input.create',compact('pos'));
    }

    public function store(Request $request, SupplierInvoiceInputService $service)
    {
        $this->authorizeInput($request);
        $data=$request->validate([
            'po_supplier_id'=>'required|integer|min:1|exists:po_suppliers,id',
            'invoice_number'=>'required|string|max:50|unique:invoice_suppliers,invoice_number',
            'invoice_date'=>'required|date_format:Y-m-d',
            'due_date'=>'nullable|date_format:Y-m-d|after_or_equal:invoice_date',
            'confirmed'=>'accepted',
        ]);
        $data['invoice_number']=trim($data['invoice_number']);
        $id=$service->create($data);
        return redirect()->route('supplier-invoice-input.show',$id)->with('success','Invoice supplier berhasil dibuat dengan status Unpaid.');
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeInput($request);
        $invoice=DB::table('invoice_suppliers as i')->join('po_suppliers as po','po.id','=','i.po_supplier_id')
            ->join('suppliers as s','s.id','=','po.supplier_id')
            ->where('i.id',$id)->select('i.*','po.po_supplier_number','s.name as supplier_name')->first();
        abort_unless($invoice,404);
        $details=DB::table('invoice_supplier_details as d')->leftJoin('products as p','p.id','=','d.product_id')
            ->where('d.invoice_supplier_id',$id)->select('d.*','p.product_code','p.name as product_name')->orderBy('d.id')->get();
        return view('supplier_invoice_input.show',compact('invoice','details'));
    }
}
