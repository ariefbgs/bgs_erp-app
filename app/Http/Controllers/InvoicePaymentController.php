<?php
namespace App\Http\Controllers;

use App\Services\InvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoicePaymentController extends Controller
{
    private function authorizePayment(Request $request): void
    {
        abort_unless($request->user() && $request->user()->canUseAdminFeatures(), 403);
    }

    public function index(Request $request, string $kind)
    {
        $this->authorizePayment($request);
        $data = $request->validate(['search' => 'nullable|string|max:100', 'status' => 'nullable|in:unpaid,paid']);
        $customer = $kind === 'customer';
        $table = $customer ? 'invoice_customers' : 'invoice_suppliers';
        $q = DB::table($table.' as i');
        if ($customer) {
            $q->join('po_customers as po', 'po.id', '=', 'i.po_customer_id')
              ->join('customers as party', 'party.id', '=', 'po.customer_id')
              ->where('i.status', '!=', 'cancelled');
            $q->addSelect('i.payment_status as paid_status');
        } else {
            $q->join('po_suppliers as po', 'po.id', '=', 'i.po_supplier_id')
              ->join('suppliers as party', 'party.id', '=', 'po.supplier_id');
            $q->addSelect('i.status as paid_status');
        }
        $field = $customer ? 'i.payment_status' : 'i.status';
        ($data['status'] ?? 'unpaid') === 'paid' ? $q->where($field, 'paid') : $q->whereIn($field, $customer ? ['sent'] : ['unpaid', 'overdue']);
        if (!empty($data['search'])) {
            $q->where(function ($query) use ($data) {
                $query->where('i.invoice_number', 'like', '%'.$data['search'].'%')
                      ->orWhere('party.name', 'like', '%'.$data['search'].'%');
            });
        }
        $invoices = $q->addSelect('i.id','i.invoice_number','i.invoice_date','i.total','party.name as party_name')
            ->orderByDesc('i.id')->paginate(8)->withQueryString();
        $payments = DB::table('invoice_payment_confirmations')->where('invoice_kind', $kind)
            ->whereIn('invoice_id', $invoices->pluck('id'))->get()->keyBy('invoice_id');
        return view('invoice_payments.index', compact('kind','invoices','payments'));
    }

    public function store(Request $request, string $kind, InvoicePaymentService $service)
    {
        $this->authorizePayment($request);
        $data = $request->validate([
            'invoice_id' => 'required|integer|min:1',
            'payment_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'reference' => 'nullable|string|max:255',
            'confirmed' => 'accepted',
        ]);
        $service->markPaid($kind, (int) $data['invoice_id'], $data['payment_date'], (int) $request->user()->id, $data['reference'] ?? null);
        return redirect()->route('invoice-payments.index', ['kind' => $kind])->with('success', 'Pembayaran dicatat. Status invoice menjadi Paid.');
    }
}
