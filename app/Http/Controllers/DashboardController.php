<?php

namespace App\Http\Controllers;

use App\Models\InvoiceCustomer;
use App\Models\Expense;
use App\Models\Quotation;
use App\Models\PoCustomer;
use App\Models\PoSupplier;
use App\Models\GoodsReceipt;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $year = date('Y');
        $months = range(1, 12);

        // ============================================================
        // 1. TOTAL NILAI (YTD)
        // ============================================================
        // Total Invoice Paid (berdasarkan payment_status = 'paid' dan year updated_at)
        $totalInvoicePaid = InvoiceCustomer::where('payment_status', 'paid')
            ->whereYear('updated_at', $year)
            ->sum('total');

        // Total Expenses (berdasarkan expense_date year)
        $totalExpenses = Expense::whereYear('expense_date', $year)->sum('amount');

        // ============================================================
        // 2. DATA BULANAN UNTUK GRAFIK (dengan running total YTD)
        // ============================================================
        // 2a. Monthly Invoice Paid (berdasarkan updated_at saat dibayar)
        $monthlyInvoicePaid = [];
        $runningInvoicePaid = 0;
        foreach ($months as $m) {
            $monthPaid = InvoiceCustomer::where('payment_status', 'paid')
                ->whereYear('updated_at', $year)
                ->whereMonth('updated_at', $m)
                ->sum('total');
            $runningInvoicePaid += $monthPaid;
            $monthlyInvoicePaid[$m] = $runningInvoicePaid;
        }

        // 2b. Monthly Expenses (berdasarkan expense_date)
        $monthlyExpense = [];
        $runningExpense = 0;
        foreach ($months as $m) {
            $monthExp = Expense::whereYear('expense_date', $year)
                ->whereMonth('expense_date', $m)
                ->sum('amount');
            $runningExpense += $monthExp;
            $monthlyExpense[$m] = $runningExpense;
        }

        // 2c. Monthly Quotation (count & total, per bulan, untuk line chart)
        $monthlyQuotation = Quotation::selectRaw('MONTH(date) as month, COUNT(*) as count, SUM(total) as total')
            ->whereYear('date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Siapkan array untuk semua bulan (agar grafik tetap 12 bulan)
        $monthlyQuotationCount = [];
        $monthlyQuotationTotal = [];
        foreach ($months as $m) {
            $monthlyQuotationCount[$m] = $monthlyQuotation->has($m) ? $monthlyQuotation[$m]->count : 0;
            $monthlyQuotationTotal[$m] = $monthlyQuotation->has($m) ? $monthlyQuotation[$m]->total : 0;
        }

        // 2d. Monthly PO Customer & PO Supplier (nilai)
        $monthlyPoCustomer = [];
        $monthlyPoSupplier = [];
        foreach ($months as $m) {
            $monthlyPoCustomer[$m] = PoCustomer::whereYear('po_date', $year)
                ->whereMonth('po_date', $m)
                ->sum('total');
            $monthlyPoSupplier[$m] = PoSupplier::whereYear('po_date', $year)
                ->whereMonth('po_date', $m)
                ->sum('total');
        }

        // 2e. Monthly Goods Receipt & Delivery Order (count)
        $monthlyGoodsReceipt = [];
        $monthlyDeliveryOrder = [];
        foreach ($months as $m) {
            $monthlyGoodsReceipt[$m] = GoodsReceipt::whereYear('receipt_date', $year)
                ->whereMonth('receipt_date', $m)
                ->count();
            $monthlyDeliveryOrder[$m] = DeliveryOrder::whereYear('delivery_date', $year)
                ->whereMonth('delivery_date', $m)
                ->count();
        }

        // ============================================================
        // 3. KATEGORI BIAYA UNTUK PIE CHART
        // ============================================================
        $expenseCategoriesData = Expense::selectRaw('expense_type, SUM(amount) as total')
            ->whereYear('expense_date', $year)
            ->groupBy('expense_type')
            ->get();

        $expenseCategories = $expenseCategoriesData->pluck('expense_type')->toArray();
        $expenseCategoryTotals = $expenseCategoriesData->pluck('total')->toArray();

        // ============================================================
        // 4. TOTAL JUMLAH DOKUMEN (untuk card stats)
        // ============================================================
        $totalQuotation = Quotation::whereYear('date', $year)->count();
        $totalQuotationValue = Quotation::whereYear('date', $year)->sum('total');

        $totalPoCustomer = PoCustomer::whereYear('po_date', $year)->count();
        $totalPoCustomerValue = PoCustomer::whereYear('po_date', $year)->sum('total');

        $totalPoSupplier = PoSupplier::whereYear('po_date', $year)->count();
        $totalPoSupplierValue = PoSupplier::whereYear('po_date', $year)->sum('total');

        $totalGoodsReceipt = GoodsReceipt::whereYear('receipt_date', $year)->count();
        $totalDeliveryOrder = DeliveryOrder::whereYear('delivery_date', $year)->count();

        $totalInvoice = InvoiceCustomer::whereYear('invoice_date', $year)->count();
        $totalInvoiceValue = InvoiceCustomer::whereYear('invoice_date', $year)->sum('total');

        // ============================================================
        // 5. DATA TERBARU UNTUK TABEL
        // ============================================================
        $recentQuotations = Quotation::with('customer')
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        $recentInvoices = InvoiceCustomer::with('poCustomer.customer')
            ->orderBy('invoice_date', 'desc')
            ->limit(5)
            ->get();

        $recentExpenses = Expense::orderBy('expense_date', 'desc')
            ->limit(5)
            ->get();

        $recentPoCustomers = PoCustomer::with('customer')
            ->orderBy('po_date', 'desc')
            ->limit(5)
            ->get();

        $recentPoSuppliers = PoSupplier::orderBy('po_date', 'desc')
            ->limit(5)
            ->get();

        $recentGoodsReceipts = GoodsReceipt::orderBy('receipt_date', 'desc')
            ->limit(5)
            ->get();

        $recentDeliveryOrders = DeliveryOrder::orderBy('delivery_date', 'desc')
            ->limit(5)
            ->get();

        // ============================================================
        // KEMBALIKAN VIEW DENGAN SEMUA DATA
        // ============================================================
        return view('dashboard', compact(
            'totalInvoicePaid',
            'totalExpenses',
            'monthlyInvoicePaid',
            'monthlyExpense',
            'monthlyQuotationCount',
            'monthlyQuotationTotal',
            'monthlyPoCustomer',
            'monthlyPoSupplier',
            'monthlyGoodsReceipt',
            'monthlyDeliveryOrder',
            'expenseCategories',
            'expenseCategoryTotals',
            'totalQuotation',
            'totalQuotationValue',
            'totalPoCustomer',
            'totalPoCustomerValue',
            'totalPoSupplier',
            'totalPoSupplierValue',
            'totalGoodsReceipt',
            'totalDeliveryOrder',
            'totalInvoice',
            'totalInvoiceValue',
            'recentQuotations',
            'recentInvoices',
            'recentExpenses',
            'recentPoCustomers',
            'recentPoSuppliers',
            'recentGoodsReceipts',
            'recentDeliveryOrders'
        ));
    }
}