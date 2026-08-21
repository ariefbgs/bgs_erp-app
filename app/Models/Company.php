<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';
    
    protected $fillable = [
        'company_code',
        'name',
        'logo',
        'address',
        'phone',
        'fax',
        'email',
        'website',
        'npwp',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'city',
        'postal_code',
        'pic_sales_support',
        'ttd_sales_support',
        'pic_do',
        'ttd_do',
        'pic_inv',
        'ttd_inv',
        'ttd_inv2',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}