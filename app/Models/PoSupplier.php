<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoSupplier extends Model
{
    protected $fillable = [
        'po_supplier_number', 'po_customer_id', 'customer_id', 'supplier_id',
        'po_date', 'status', 'notes',
        'subtotal', 'discount_percent', 'discount_amount',
        'tax_percent', 'tax_amount', 'total'
    ];

    protected $dates = ['po_date'];

    public function poCustomer()
    {
        return $this->belongsTo(PoCustomer::class, 'po_customer_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details()
    {
        return $this->hasMany(PoSupplierDetail::class);
    }

    public function updateReceiptStatus()
    {
        $totalOrdered = $this->details->sum('quantity');
        $totalReceived = $this->goodsReceipts()
            ->where('status', 'completed')
            ->with('details')
            ->get()
            ->sum(function($receipt) {
                return $receipt->details->sum('quantity_received');
            });
        
        if ($totalReceived >= $totalOrdered) {
            $this->receipt_status = 'complete';
        } elseif ($totalReceived > 0) {
            $this->receipt_status = 'partial';
        } else {
            $this->receipt_status = 'pending';
        }
        $this->save();
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function goodsReceiptDetails()
    {
        return $this->hasMany(GoodsReceiptDetail::class, 'po_supplier_detail_id');
    }
}