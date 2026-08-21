<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    
    protected $fillable = [
        'product_code',
        'product_code2',
        'name',
        'name2',
        'specification',
        'brand',
        'unit',
        'price',
        'purchase_price',
        'stock',
        'description',
        'image'
    ];

    public function quotationDetails() { return $this->hasMany(QuotationDetail::class); }
    public function invoiceCustomerDetails() { return $this->hasMany(InvoiceCustomerDetail::class); }
    public function deliveryOrderDetails() { return $this->hasMany(DeliveryOrderDetail::class); }
    public function purchaseOrderDetails() { return $this->hasMany(PurchaseOrderDetail::class); }
}