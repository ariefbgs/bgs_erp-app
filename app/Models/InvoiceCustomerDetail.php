<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCustomerDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_customer_id', 'product_id', 'quantity', 'unit_price', 'subtotal'
    ];

    public function invoiceCustomer()
    {
        return $this->belongsTo(InvoiceCustomer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}