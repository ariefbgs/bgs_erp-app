<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptDetail extends Model
{
    protected $fillable = [
        'goods_receipt_id', 'po_supplier_detail_id', 'product_id',
        'quantity_received', 'quantity_remaining', 'notes'
    ];

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function poSupplierDetail()
    {
        return $this->belongsTo(PoSupplierDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}