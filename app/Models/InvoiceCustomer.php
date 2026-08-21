<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCustomer extends Model
{
    use HasFactory; 

    protected $fillable = [
        'invoice_number', 'type', 'po_customer_id', 'invoice_date', 'due_date', 'tax_invoice_number', 'tax_invoice_attachment',
        'subtotal', 'discount_percent', 'discount_amount', 'tax_percent', 'tax_amount',
        'pph23_percent', 'pph23_amount', 'total', 'dp_amount', 'dp_percent', 'remaining_amount', 
        'status', 'payment_terms', 'parent_invoice_id', 'payment_status', 'delivery_time', 'notes'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function poCustomer()
    {
        return $this->belongsTo(PoCustomer::class);
    }

    public function details()
    {
        return $this->hasMany(InvoiceCustomerDetail::class);
    }

    public function parentInvoice()
    {
        return $this->belongsTo(InvoiceCustomer::class, 'parent_invoice_id');
    }

    public function childInvoices()
    {
        return $this->hasMany(InvoiceCustomer::class, 'parent_invoice_id');
    }
}