<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Sancion;
use Illuminate\Support\Facades\Auth;

class VerificarSanciones
{
    public function handle(Request $request, Closure $next, ...$restricciones)
    {
        $usuario = Auth::user();
        
        if (!$usuario) {
            return $next($request);
        }

        // Verificar sanciones activas
        $sancionesActivas = Sancion::activas()
            ->where('usuario_id', $usuario->id)
            ->get();

        if ($sancionesActivas->isEmpty()) {
            return $next($request);
        }

        // Verificar restricciones específicas
        foreach ($sancionesActivas as $sancion) {
            if ($sancion->restricciones && count(array_intersect($restricciones, $sancion->restricciones)) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta tiene restricciones activas. Razón: ' . $sancion->razon,
                    'sancion' => [
                        'tipo' => $sancion->tipo,
                        'razon' => $sancion->razon,
                        'fecha_fin' => $sancion->fecha_fin
                    ]
                ], 403);
            }
        }

        return $next($request);
    }
}