<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'po_customer_id',

        // Transitional legacy fields.
        // Preserved until all DR views/print are migrated.

        'receipt_date',
        'attachment',
        'notes',
        'status',
    ];

    public function poCustomer()
    {
        return $this->belongsTo(PoCustomer::class);
    }

    public function deliveryOrders()
    {
        return $this->belongsToMany(
            DeliveryOrder::class,
            'document_receipt_delivery_orders'
        )->withTimestamps();
    }

    public function invoices()
    {
        return $this->belongsToMany(
            InvoiceCustomer::class,
            'document_receipt_invoices'
        )->withTimestamps();
    }

}
