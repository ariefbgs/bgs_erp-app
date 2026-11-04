<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCustomerDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_customer_id',
        'po_customer_detail_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal'
    ];

    public function invoiceCustomer()
    {
        return $this->belongsTo(InvoiceCustomer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function poCustomerDetail()
    {
        return $this->belongsTo(
            PoCustomerDetail::class,
            'po_customer_detail_id'
        );
    }
}