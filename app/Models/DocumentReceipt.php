<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'delivery_order_id',
        'receipt_date',
        'invoice_number',
        'tax_invoice_number',
        'attachment',
        'notes',
        'status',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}