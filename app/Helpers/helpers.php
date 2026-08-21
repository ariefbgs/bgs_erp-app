<?php

use App\Models\Company;

if (!function_exists('getActiveCompany')) {
    function getActiveCompany() {
        return Company::where('is_active', true)->first();
    }
}