<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_number', 
        'customer_id', 
        'date', 
        'valid_until',
        'status', 
        'subtotal', 
        'discount_percent', 
        'discount_amount',
        'dpp',
        'tax_percent', 
        'tax_amount', 
        'pph23_percent', 
        'pph23_amount',
        'total', 
        'notes',
        'payment_terms',
        'delivery_time',
        'show_image_on_print'
    ];

    protected $casts = [
        'show_image_on_print' => 'boolean',
        'date' => 'date',
        'valid_until' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class);
    }

    // Jika ingin tetap pakai nama method items() untuk konsistensi
    public function items()
    {
        return $this->hasMany(QuotationDetail::class);
    }

    // Auto set valid_until = date + 7 days
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($quotation) {
            if (empty($quotation->valid_until)) {
                $quotation->valid_until = date('Y-m-d', strtotime($quotation->date . ' +7 days'));
            }
        });
    }
}