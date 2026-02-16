<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Mascota;
use App\Models\Veterinario;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MetricasController extends Controller
{
    /**
     * Obtener métricas principales del sistema
     */
    public function obtenerMetricas(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            // Métricas básicas
            $totalUsuarios = Usuario::count();
            $totalMascotas = Mascota::count();
            $totalVeterinarios = Veterinario::where('estado', Veterinario::ESTADO_APROBADO)->count();
            
            // Adopciones - usar HistorialTransferencia si existe
            $totalAdopciones = 0;
            if (class_exists('App\Models\HistorialTransferenciaMascota')) {
                $totalAdopciones = \App\Models\HistorialTransferenciaMascota::where('motivo', 'adopcion')->count();
            }
            
            // Veterinarios pendientes de aprobación
            $veterinariosPendientes = Veterinario::where('estado', Veterinario::ESTADO_PENDIENTE)->count();
            
            // Mascotas en adopción (si tienes el modelo OfertaAdopcion)
            $mascotasEnAdopcion = 0;
            if (class_exists('App\Models\OfertaAdopcion')) {
                $mascotasEnAdopcion = \App\Models\OfertaAdopcion::where('estado_oferta', 'publicada')->count();
            }
            
            // Mascotas dadas de baja
            $mascotasDadasDeBaja = Mascota::onlyTrashed()->count();
            
            // Crecimiento del último mes
            $fechaHaceUnMes = Carbon::now()->subMonth();
            
            $usuariosUltimoMes = Usuario::where('created_at', '>=', $fechaHaceUnMes)->count();
            $mascotasUltimoMes = Mascota::where('created_at', '>=', $fechaHaceUnMes)->count();
            
            // Adopciones del último mes
            $adopcionesUltimoMes = 0;
            if (class_exists('App\Models\HistorialTransferenciaMascota')) {
                $adopcionesUltimoMes = \App\Models\HistorialTransferenciaMascota::where('motivo', 'adopcion')
                    ->where('created_at', '>=', $fechaHaceUnMes)
                    ->count();
            }
            
            // Porcentajes de crecimiento
            $crecimientoUsuarios = $totalUsuarios > 0 
                ? round(($usuariosUltimoMes / $totalUsuarios) * 100, 2)
                : 0;
                
            $crecimientoMascotas = $totalMascotas > 0 
                ? round(($mascotasUltimoMes / $totalMascotas) * 100, 2)
                : 0;
                
            $crecimientoAdopciones = $totalAdopciones > 0 
                ? round(($adopcionesUltimoMes / $totalAdopciones) * 100, 2)
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'metricas_principales' => [
                        'usuarios' => [
                            'total' => $totalUsuarios,
                            'ultimo_mes' => $usuariosUltimoMes,
                            'crecimiento' => $crecimientoUsuarios,
                            'icono' => 'users',
                            'color' => 'blue'
                        ],
                        'mascotas' => [
                            'total' => $totalMascotas,
                            'ultimo_mes' => $mascotasUltimoMes,
                            'crecimiento' => $crecimientoMascotas,
                            'en_adopcion' => $mascotasEnAdopcion,
                            'dadas_de_baja' => $mascotasDadasDeBaja,
                            'icono' => 'paw',
                            'color' => 'green'
                        ],
                        'veterinarios' => [
                            'total' => $totalVeterinarios,
                            'pendientes_aprobacion' => $veterinariosPendientes,
                            'icono' => 'stethoscope',
                            'color' => 'red'
                        ],
                        'adopciones' => [
                            'total' => $totalAdopciones,
                            'ultimo_mes' => $adopcionesUltimoMes,
                            'crecimiento' => $crecimientoAdopciones,
                            'icono' => 'heart',
                            'color' => 'purple'
                        ]
                    ],
                    'estadisticas_generales' => [
                        'total_registros' => $totalUsuarios + $totalMascotas + $totalVeterinarios,
                        'tasa_adopcion' => $totalMascotas > 0 
                            ? round(($totalAdopciones / $totalMascotas) * 100, 2)
                            : 0,
                        'ratio_mascotas_usuario' => $totalUsuarios > 0 
                            ? round($totalMascotas / $totalUsuarios, 2)
                            : 0
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en obtenerMetricas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las métricas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas detalladas de adopciones
     */
    public function estadisticasAdopciones(Request $request)
    {
        try {
            // Verificar autenticación
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            // Adopciones por mes (últimos 6 meses)
            $adopcionesPorMes = [];
            for ($i = 5; $i >= 0; $i--) {
                $mes = Carbon::now()->subMonths($i);
                $mesInicio = $mes->copy()->startOfMonth();
                $mesFin = $mes->copy()->endOfMonth();
                
                $totalMes = 0;
                
                // Si tienes HistorialTransferenciaMascota
                if (class_exists('App\Models\HistorialTransferenciaMascota')) {
                    $totalMes = \App\Models\HistorialTransferenciaMascota::where('motivo', 'adopcion')
                        ->whereBetween('created_at', [$mesInicio, $mesFin])
                        ->count();
                }
                
                $adopcionesPorMes[] = [
                    'mes' => $mes->translatedFormat('F Y'),
                    'mes_corto' => $mes->translatedFormat('M'),
                    'total' => $totalMes
                ];
            }
            
            // Total de adopciones
            $totalAdopciones = 0;
            if (class_exists('App\Models\HistorialTransferenciaMascota')) {
                $totalAdopciones = \App\Models\HistorialTransferenciaMascota::where('motivo', 'adopcion')->count();
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_adopciones' => $totalAdopciones,
                    'adopciones_por_mes' => $adopcionesPorMes,
                    'adopciones_mes_actual' => $adopcionesPorMes[5]['total'] ?? 0,
                    'adopciones_mes_anterior' => $adopcionesPorMes[4]['total'] ?? 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en estadisticasAdopciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de adopciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener actividad reciente del sistema
     */
    public function actividadReciente(Request $request)
    {
        try {
            // Verificar autenticación
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            // Últimos usuarios registrados (5 más recientes)
            $ultimosUsuarios = Usuario::with('user')
                ->latest()
                ->take(5)
                ->get()
                ->map(function($usuario) {
                    return [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'fecha_registro' => $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : 'Sin fecha',
                        'email' => $usuario->user ? $usuario->user->email : 'Sin email'
                    ];
                })->toArray();
            
            // Últimas mascotas registradas (5 más recientes)
            $ultimasMascotas = Mascota::with('usuario')
                ->latest()
                ->take(5)
                ->get()
                ->map(function($mascota) {
                    return [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'especie' => $mascota->especie,
                        'fecha_registro' => $mascota->created_at ? $mascota->created_at->format('d/m/Y H:i') : 'Sin fecha',
                        'tutor' => $mascota->usuario ? $mascota->usuario->nombre : 'Sin tutor'
                    ];
                })->toArray();
            
            // Últimas adopciones (si existe el modelo)
            $ultimasAdopciones = [];
            if (class_exists('App\Models\HistorialTransferenciaMascota')) {
                $ultimasAdopciones = \App\Models\HistorialTransferenciaMascota::with(['mascota', 'tutorNuevo'])
                    ->where('motivo', 'adopcion')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function($transferencia) {
                        return [
                            'id' => $transferencia->id,
                            'mascota' => $transferencia->mascota ? $transferencia->mascota->nombre : 'Mascota no disponible',
                            'nuevo_tutor' => $transferencia->tutorNuevo ? $transferencia->tutorNuevo->nombre : 'Sin tutor',
                            'fecha_adopcion' => $transferencia->fecha_transferencia ? $transferencia->fecha_transferencia->format('d/m/Y') : 'Sin fecha'
                        ];
                    })->toArray();
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'ultimos_usuarios' => $ultimosUsuarios,
                    'ultimas_mascotas' => $ultimasMascotas,
                    'ultimas_adopciones' => $ultimasAdopciones,
                    'timestamp' => now()->format('d/m/Y H:i:s')
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en actividadReciente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividad reciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}