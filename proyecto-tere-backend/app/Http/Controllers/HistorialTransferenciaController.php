<?php

namespace App\Http\Controllers;

use App\Models\HistorialTransferenciaMascota;
use App\Models\Mascota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistorialTransferenciaController extends Controller
{
    /**
     * Obtener historial de una mascota específica
     */
    public function porMascota($mascotaId)
    {
        try {
            $user = Auth::user();
            $mascota = Mascota::findOrFail($mascotaId);
            
            // Verificar que el usuario tiene relación con la mascota
            if ($mascota->usuario_id !== $user->id && 
                !$mascota->transferencias()->where(function($q) use ($user) {
                    $q->where('tutor_anterior_id', $user->id)
                      ->orWhere('tutor_nuevo_id', $user->id);
                })->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver el historial de esta mascota'
                ], 403);
            }
            
            $historial = HistorialTransferenciaMascota::with([
                'tutorAnterior',
                'tutorNuevo',
                'solicitud',
                'proceso'
            ])
            ->where('mascota_id', $mascotaId)
            ->orderBy('fecha_transferencia', 'desc')
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'tutor_actual_id' => $mascota->usuario_id,
                        'total_transferencias' => $historial->count()
                    ],
                    'historial' => $historial,
                    'linea_temporal' => $this->generarLineaTemporal($historial)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial'
            ], 500);
        }
    }
    
    /**
     * Obtener historial de un usuario (como tutor anterior o nuevo)
     */
    public function porUsuario(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = HistorialTransferenciaMascota::with([
                'mascota',
                'tutorAnterior',
                'tutorNuevo'
            ])
            ->where(function($q) use ($user) {
                $q->where('tutor_anterior_id', $user->id)
                  ->orWhere('tutor_nuevo_id', $user->id);
            });
            
            // Filtrar por tipo de participación
            if ($request->has('rol')) {
                if ($request->rol === 'cedente') {
                    $query->where('tutor_anterior_id', $user->id);
                } elseif ($request->rol === 'receptor') {
                    $query->where('tutor_nuevo_id', $user->id);
                }
            }
            
            // Filtrar por motivo
            if ($request->has('motivo')) {
                $query->where('motivo', $request->motivo);
            }
            
            $historial = $query->orderBy('fecha_transferencia', 'desc')
                              ->paginate(15);
            
            $estadisticas = [
                'total' => $historial->total(),
                'como_cedente' => HistorialTransferenciaMascota::where('tutor_anterior_id', $user->id)->count(),
                'como_receptor' => HistorialTransferenciaMascota::where('tutor_nuevo_id', $user->id)->count(),
                'por_motivo' => HistorialTransferenciaMascota::where(function($q) use ($user) {
                    $q->where('tutor_anterior_id', $user->id)
                      ->orWhere('tutor_nuevo_id', $user->id);
                })->groupBy('motivo')->selectRaw('motivo, count(*) as total')->pluck('total', 'motivo')
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'historial' => $historial,
                    'estadisticas' => $estadisticas,
                    'usuario' => [
                        'id' => $user->id,
                        'nombre' => $user->nombre ?? $user->name
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial'
            ], 500);
        }
    }
    
    /**
     * Generar línea temporal para visualización
     */
    private function generarLineaTemporal($historial)
    {
        return $historial->map(function($transferencia, $index) use ($historial) {
            return [
                'id' => $transferencia->id_transferencia,
                'fecha' => $transferencia->fecha_transferencia->format('d/m/Y'),
                'hora' => $transferencia->fecha_transferencia->format('H:i'),
                'desde' => $transferencia->tutorAnterior->nombre ?? 'Desconocido',
                'hacia' => $transferencia->tutorNuevo->nombre ?? 'Desconocido',
                'motivo' => $transferencia->motivo,
                'observaciones' => $transferencia->observaciones,
                'es_ultima' => $index === 0,
                'es_primera' => $index === $historial->count() - 1
            ];
        });
    }
}