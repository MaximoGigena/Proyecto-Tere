<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificacionController extends Controller
{
    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // 🔥 CORREGIDO: Obtener el ID del perfil (Usuario) no el ID de autenticación (User)
        $usuario = $user->userable;
        
        if (!$usuario) {
            Log::warning('Usuario sin perfil asociado al consultar notificaciones', [
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'notificaciones' => [],
                    'paginacion' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 15,
                        'total' => 0
                    ]
                ],
                'total_no_leidas' => 0
            ]);
        }
        
        Log::info('Consultando notificaciones', [
            'auth_user_id' => $user->id,
            'usuario_id' => $usuario->id,
            'buscando_con_user_id' => $usuario->id // 👈 Usando el ID del perfil
        ]);
        
        // ✅ Usar usuario->id (ID del perfil) para buscar notificaciones
        $query = Notificacion::where('user_id', $usuario->id)
            ->where('activa', true);
        
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->filled('leida')) {
            $query->where('leida', $request->leida === 'true');
        }
        
        $query->orderBy('created_at', 'desc');
        
        $perPage = $request->get('per_page', 15);
        $notificaciones = $query->paginate($perPage);
        
        // Contar no leídas usando el mismo criterio
        $totalNoLeidas = Notificacion::where('user_id', $usuario->id)
            ->where('activa', true)
            ->where('leida', false)
            ->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'notificaciones' => $notificaciones->items(),
                'paginacion' => [
                    'current_page' => $notificaciones->currentPage(),
                    'last_page' => $notificaciones->lastPage(),
                    'per_page' => $notificaciones->perPage(),
                    'total' => $notificaciones->total()
                ]
            ],
            'total_no_leidas' => $totalNoLeidas
        ]);
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($id)
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario sin perfil asociado'
            ], 404);
        }
        
        $notificacion = Notificacion::where('user_id', $usuario->id)
            ->where('activa', true)
            ->findOrFail($id);
        
        $notificacion->marcarComoLeida();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }
    
    /**
     * Marcar todas como leídas
     */
    public function marcarTodasComoLeidas()
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario sin perfil asociado'
            ], 404);
        }
        
        $actualizadas = Notificacion::where('user_id', $usuario->id)
            ->where('activa', true)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_lectura' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas',
            'actualizadas' => $actualizadas
        ]);
    }
    
    /**
     * Eliminar notificación (desactivar)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario sin perfil asociado'
            ], 404);
        }
        
        $notificacion = Notificacion::where('user_id', $usuario->id)
            ->where('activa', true)
            ->findOrFail($id);
        
        $notificacion->desactivar();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada'
        ]);
    }
    
    /**
     * Obtener estadísticas de notificaciones
     */
    public function estadisticas()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            $usuario = $user->userable;
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario sin perfil asociado'
                ], 404);
            }
            
            Log::info('Calculando estadísticas de notificaciones', [
                'auth_user_id' => $user->id,
                'usuario_id' => $usuario->id,
                'usando_user_id' => $usuario->id
            ]);
            
            // ✅ Usar usuario->id para todas las consultas
            $estadisticas = DB::table('notificaciones')
                ->where('user_id', $usuario->id)
                ->where('activa', true)
                ->select('tipo', DB::raw('COUNT(*) as total'))
                ->groupBy('tipo')
                ->orderBy('tipo')
                ->pluck('total', 'tipo');

            $total = DB::table('notificaciones')
                ->where('user_id', $usuario->id)
                ->where('activa', true)
                ->count();
                
            $noLeidas = DB::table('notificaciones')
                ->where('user_id', $usuario->id)
                ->where('activa', true)
                ->where('leida', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'no_leidas' => $noLeidas,
                    'por_tipo' => $estadisticas
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en estadísticas de notificaciones: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar estadísticas'
            ], 500);
        }
    }
}