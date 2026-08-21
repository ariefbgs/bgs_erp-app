<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManual extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'module_name', 
        'type',
        'title', 
        'content'
    ];

    // Relasi ke Panduan Induknya
    public function parent()
    {
        return $this->belongsTo(UserManual::class, 'parent_id');
    }

    // Relasi ke Sub-Modul di bawahnya
    public function children()
    {
        return $this->hasMany(UserManual::class, 'parent_id')->orderBy('type', 'asc');
    }
}