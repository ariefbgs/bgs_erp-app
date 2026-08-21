<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    use HasFactory;

    protected $table = 'quotation_details'; // jika nama tabel berbeda, sesuaikan

    protected $fillable = [
        'quotation_id', 
        'product_id', 
        'specification', // Tambahkan ini
        'description',   // Tambahkan ini
        'quantity', 
        'unit_price', 
        'subtotal'
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}