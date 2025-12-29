<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspended
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {

        Log::info('🔄 MIDDLEWARE CheckUserSuspended EJECUTÁNDOSE', [
            'path' => $request->path(),
            'method' => $request->method(),
            'is_api' => $request->is('api/*'),
            'full_url' => $request->fullUrl()
        ]);

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            
            // DEBUG: Verificar qué devuelve el método
            Log::info('🔍 CheckUserSuspended - Verificando usuario', [
                'user_id' => $user->id,
                'email' => $user->email,
                'estado' => $user->estado,
                'tieneSancionesActivas' => $user->tieneSancionesActivas(),
                'current_path' => $request->path()
            ]);
            
            // Verificar si el usuario tiene estado suspendido
            if ($user->estado === 'suspendido' || $user->estado === 'bloqueado') {
                Log::info('🚫 Usuario suspendido por estado', [
                    'user_id' => $user->id,
                    'estado' => $user->estado,
                    'path' => $request->path()
                ]);
                
                return $this->redirectToSuspended($request);
            }
            
            // Verificar sanciones activas
            if ($user->tieneSancionesActivas()) {
                Log::info('🚫 Usuario con sanciones activas', [
                    'user_id' => $user->id,
                    'path' => $request->path()
                ]);
                
                return $this->redirectToSuspended($request);
            }
        }

        return $next($request);
    }
    
    /**
     * Redirigir a la vista de cuenta suspendida
     */
    private function redirectToSuspended(Request $request)
    {
        $excludedPaths = [
            'api/logout',
            'logout',
            'cuenta-suspendida',
            'api/usuario/sancion-activa',
        ];

        $currentPath = $request->path();

        // Si ya está en una ruta excluida, permitir continuar
        // PERO no tenemos $next aquí, así que simplemente no hacemos nada
        // Estas rutas deberían manejarse en el handle() antes
        if (in_array($currentPath, $excludedPaths) || str_contains($currentPath, 'cuenta-suspendida')) {
            // No redirigir, permitir acceso
            return response()->json(['success' => true], 200);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta está suspendida',
                'redirect_to' => '/cuenta-suspendida',
                'code' => 'ACCOUNT_SUSPENDED'
            ], 403);
        }

        return redirect('/cuenta-suspendida');
    }
}