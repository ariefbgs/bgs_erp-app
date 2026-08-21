<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'address',
        'shipping_address',
        'document_address',
        'tax_number',
        'pic_quotation_name',
        'pic_quotation_phone',
        'pic_do_name',
        'pic_do_phone',
        'pic_invoice_name',
        'pic_invoice_phone',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function poCustomers()
    {
        return $this->hasMany(PoCustomer::class);
    }

    public function invoiceCustomers()
    {
        return $this->hasMany(InvoiceCustomer::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }
}