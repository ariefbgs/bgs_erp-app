<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'do_number', 
        'po_customer_id', 
        'delivery_date', 
        'shipping_address', 
        'receiver_name', 
        'invoice_number',
        'tax_invoice_number',
        'status', 
        'delivery_by',
        'attachment',
        'notes', 
        'created_at', 
        'updated_at'
    ];
    public function poCustomer() { return $this->belongsTo(PoCustomer::class); }
    public function details() { return $this->hasMany(DeliveryOrderDetail::class); }
}
