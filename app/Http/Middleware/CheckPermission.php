<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ) {
        $user = $request->user();

        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
        }

        return $next($request);
    }
}
