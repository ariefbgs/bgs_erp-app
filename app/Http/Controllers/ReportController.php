<?php

namespace App\Http\Controllers;

use App\Models\PoSupplier;
use App\Models\PoCustomer;
use App\Models\InvoiceSupplier; // asumsikan ada
use App\Models\InvoiceCustomer;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceipt;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PurchasingExport;
use App\Exports\SalesExport;
use App\Exports\PaymentSupplierExport;
use App\Exports\PaymentCustomerExport;
use App\Exports\DeliveryExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }
    // LAPORAN PEMBELIAN (PDF)
    public function purchasingPdf(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');

        $poSuppliers = PoSupplier::with('supplier')
            ->whereBetween('po_date', [$startDate, $endDate])
            ->orderBy('po_date', 'desc')
            ->get();

        $goodsReceipts = GoodsReceipt::with('poSupplier.supplier')
            ->whereBetween('receipt_date', [$startDate, $endDate])
            ->orderBy('receipt_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.purchasing_pdf', compact('poSuppliers', 'goodsReceipts', 'startDate', 'endDate'));
        return $pdf->download('laporan_pembelian.pdf');
    }

    // LAPORAN PEMBELIAN (EXCEL)
    public function purchasingExcel(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        return Excel::download(new PurchasingExport($startDate, $endDate), 'laporan_pembelian.xlsx');
    }

    // LAPORAN PENJUALAN (PDF)
    public function salesPdf(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');

        $poCustomers = PoCustomer::with('customer')
            ->whereBetween('po_date', [$startDate, $endDate])
            ->orderBy('po_date', 'desc')
            ->get();

        $deliveryOrders = DeliveryOrder::with('poCustomer.customer')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->orderBy('delivery_date', 'desc')
            ->get();

        $invoices = InvoiceCustomer::with('poCustomer.customer')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->orderBy('invoice_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.sales_pdf', compact('poCustomers', 'deliveryOrders', 'invoices', 'startDate', 'endDate'));
        return $pdf->download('laporan_penjualan.pdf');
    }

    public function salesExcel(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        return Excel::download(new SalesExport($startDate, $endDate), 'laporan_penjualan.xlsx');
    }

    // LAPORAN PAYMENT KE SUPPLIER (asumsikan ada model InvoiceSupplier)
    public function paymentSupplierPdf(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');

        $invoices = InvoiceSupplier::with('poSupplier.supplier')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->orderBy('invoice_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.payment_supplier_pdf', compact('invoices', 'startDate', 'endDate'));
        return $pdf->download('laporan_payment_supplier.pdf');
    }

    public function paymentSupplierExcel(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        return Excel::download(new PaymentSupplierExport($startDate, $endDate), 'laporan_payment_supplier.xlsx');
    }

    // LAPORAN PAYMENT DARI CUSTOMER (InvoiceCustomer)
    public function paymentCustomerPdf(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');

        $invoices = InvoiceCustomer::with('poCustomer.customer')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->orderBy('invoice_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.payment_customer_pdf', compact('invoices', 'startDate', 'endDate'));
        return $pdf->download('laporan_payment_customer.pdf');
    }

    public function paymentCustomerExcel(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        return Excel::download(new PaymentCustomerExport($startDate, $endDate), 'laporan_payment_customer.xlsx');
    }

    // LAPORAN DELIVERY
    public function deliveryPdf(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');

        $deliveries = DeliveryOrder::with('poCustomer.customer')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->orderBy('delivery_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.delivery_pdf', compact('deliveries', 'startDate', 'endDate'));
        return $pdf->download('laporan_delivery.pdf');
    }

    public function deliveryExcel(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        return Excel::download(new DeliveryExport($startDate, $endDate), 'laporan_delivery.xlsx');
    }
}