<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code', 'name', 'email', 'phone', 'address', 'tax_number'
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function posuppliers()
    {
        return $this->hasMany(Posupplier::class);
    }
}