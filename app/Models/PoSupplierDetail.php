<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoSupplierDetail extends Model
{
    protected $table = 'po_supplier_details';
    
    protected $fillable = [
        'po_supplier_id',
        'product_id',
        'quantity',
        'purchase_price',
        'subtotal'
    ];
    
    public function poSupplier()
    {
        return $this->belongsTo(PoSupplier::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceiptDetails()
    {
        return $this->hasMany(GoodsReceiptDetail::class);
    }

    public function getReceivedQuantityAttribute()
    {
        return $this->goodsReceiptDetails()
            ->whereHas('goodsReceipt', function($q) {
                $q->where('status', 'completed');
            })
            ->sum('quantity_received');
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->received_quantity;
    }
}