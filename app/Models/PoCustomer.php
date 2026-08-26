<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoCustomer extends Model
{
    use HasFactory;

    protected $table = 'po_customers';
    
    protected $fillable = [
        'po_number',
        'customer_id',
        'quotation_id',
        'payment_terms',
        'delivery_time',
        'po_date',
        'delivery_date',
        'status',
        'invoice_status',
        'procurement_status',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount_percent',
        'discount_amount',
        'total',
        'remaining_amount',
        'attachment',
        'notes'
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'remaining_amount' => 'decimal:2',
    ];

    // Default values (bisa juga di migrasi)
    protected $attributes = [
        'invoice_status' => 'issue yet',
        'status' => 'draft',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function details()
    {
        return $this->hasMany(PoCustomerDetail::class);
    }

    // Relasi ke PO Supplier (jika ada)
    public function poSuppliers()
    {
        return $this->hasMany(PoSupplier::class, 'po_customer_id');
    }

    // Relasi ke Invoice Customer (untuk partial payment)
    public function invoiceCustomers()
    {
        return $this->hasMany(InvoiceCustomer::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    // =============================================
    // HELPER METHODS untuk fitur multi invoice
    // =============================================

    /**
     * Hitung total yang sudah diinvoice (tidak termasuk yang dibatalkan)
     */
    public function getTotalInvoicedAttribute()
    {
        return $this->invoiceCustomers()
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    /**
     * Hitung sisa tagihan yang belum diinvoice
     */
    public function getRemainingAttribute()
    {
        return max(0, $this->total - $this->total_invoiced);
    }

    /**
     * Cek apakah PO sudah lunas (completed)
     */
    public function getIsFullyInvoicedAttribute()
    {
        return $this->remaining <= 0;
    }

    /**
     * Update invoice_status berdasarkan total invoiced
     * Dipanggil setelah ada perubahan invoice
     */
    public function updateInvoiceStatus()
    {
        $poPreTax = max(0, (float) $this->subtotal);
        if ($poPreTax <= 0) {
            $poPreTax = max(0, (float) $this->total - (float) $this->tax_amount);
        }
        $totalInvoicedPreTax = (float) $this->invoiceCustomers()
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(CASE WHEN dp_amount > 0 THEN dp_amount ELSE subtotal END), 0) as amount')
            ->value('amount');
        $remainingPreTax = max(0, $poPreTax - $totalInvoicedPreTax);
        
        if (round($remainingPreTax) <= 0) {
            $status = 'completed';
        } elseif ($totalInvoicedPreTax > 0) {
            $status = 'partial';
        } else {
            $status = 'issue yet';
        }
        
        $remainingAmount = $remainingPreTax;

        $this->update([
            'invoice_status' => $status,
            'remaining_amount' => $remainingAmount,
        ]);
        return $this;
    }

    /**
     * Ambil daftar invoice yang status partial (bisa dijadikan parent)
     */
    public function getPartialInvoicesAttribute()
    {
        return $this->invoiceCustomers()
            ->where('status', 'partial')
            ->where('payment_status', '!=', 'paid')
            ->get();
    }
}
