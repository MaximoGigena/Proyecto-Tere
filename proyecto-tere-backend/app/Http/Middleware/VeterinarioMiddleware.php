<?php
// app/Http/Middleware/VeterinarioMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VeterinarioMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user || !$user->userable || $user->userable_type !== 'App\Models\Veterinario') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo para veterinarios.'
            ], 403);
        }

        return $next($request);
    }
}