<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderDetail extends Model
{
    protected $fillable = ['delivery_order_id', 'product_id', 'quantity'];
    public function product() { return $this->belongsTo(Product::class); }
    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
}
