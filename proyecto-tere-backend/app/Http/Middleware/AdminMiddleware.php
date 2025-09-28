<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->isAdministrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos de administrador'
            ], 403);
        }

        return $next($request);
    }
}

