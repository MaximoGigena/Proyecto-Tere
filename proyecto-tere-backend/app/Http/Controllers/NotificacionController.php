<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        
        $query = $usuario->notificaciones();
        
        // Filtrar por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        
        // Filtrar por leídas/no leídas
        if ($request->filled('leida')) {
            $query->where('leida', $request->leida === 'true');
        }
        
        // Paginación
        $perPage = $request->get('per_page', 15);
        $notificaciones = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $notificaciones,
            'total_no_leidas' => $usuario->contarNotificacionesNoLeidas()
        ]);
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($id)
    {
        $notificacion = Notificacion::where('usuario_id', Auth::id())
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
        Notificacion::where('usuario_id', Auth::id())
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_lectura' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }
    
    /**
     * Eliminar notificación
     */
    public function destroy($id)
    {
        $notificacion = Notificacion::where('usuario_id', Auth::id())
            ->findOrFail($id);
        
        $notificacion->desactivar();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada'
        ]);
    }
    
    /**
     * Obtener estadísticas
     */
    public function estadisticas()
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        
        $estadisticas = [
            'total' => $usuario->notificaciones()->count(),
            'no_leidas' => $usuario->contarNotificacionesNoLeidas(),
            'por_tipo' => $usuario->notificaciones()
                ->selectRaw('tipo, COUNT(*) as total')
                ->groupBy('tipo')
                ->pluck('total', 'tipo')
                ->toArray()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $estadisticas
        ]);
    }
}