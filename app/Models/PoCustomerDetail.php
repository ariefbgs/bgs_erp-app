<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoCustomerDetail extends Model
{
    use HasFactory;

    protected $table = 'po_customer_details';
    
    protected $fillable = [
        'po_customer_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal'
    ];

    public function poCustomer()
    {
        return $this->belongsTo(PoCustomer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}