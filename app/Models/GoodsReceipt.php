<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'po_supplier_id', 'receipt_date', 
        'status', 'notes', 'received_by'
    ];

    protected $dates = ['receipt_date'];

    public function poSupplier()
    {
        return $this->belongsTo(PoSupplier::class);
    }

    public function details()
    {
        return $this->hasMany(GoodsReceiptDetail::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    // Relasi ke SupplierInvoice (faktur pembelian)
    public function supplierInvoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }
}