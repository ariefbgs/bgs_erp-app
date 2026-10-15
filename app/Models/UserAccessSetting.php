<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserAccessSetting extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['full_features' => 'boolean', 'dashboard_access' => 'boolean'];
    public function user() { return $this->belongsTo(User::class); }
}
