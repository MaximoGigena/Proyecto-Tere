<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserType
{
    // app/Http/Middleware/CheckUserType.php
    public function handle(Request $request, Closure $next, ...$types)
    {
        $user = $request->user();
        
        if (!$user || !in_array($user->userable_type, array_map(function($type) {
            return 'App\Models\\' . ucfirst($type);
        }, $types))) {
            abort(403, 'Acceso no autorizado');
        }

        return $next($request);
    }
}