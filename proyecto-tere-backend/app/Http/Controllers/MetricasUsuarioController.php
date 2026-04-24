<?php
// app/Http/Controllers/Api/MetricasUsuarioController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UbicacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetricasUsuarioController extends Controller
{
    public function obtenerMetricasGranulares(Request $request)
    {
        try {
            $validated = $request->validate([
                'reporte' => 'required|in:volumen,crecimiento,actividad,comportamiento,calidad',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'tipo_usuario' => 'nullable|string|in:usuario,veterinario,admin',
                'estado' => 'nullable|string|in:activo,inactivo,bloqueado,verificado,no_verificado',
                'agrupacion' => 'nullable|in:diaria,semanal,mensual,trimestral,anual',
                'granularidad' => 'nullable|in:dia,semana,mes,tipo_usuario,ubicacion,estado'
            ]);

            $fechaDesde = $validated['fecha_desde'] ?? now()->subDays(30)->format('Y-m-d');
            $fechaHasta = $validated['fecha_hasta'] ?? now()->format('Y-m-d');
            $agrupacion = $validated['agrupacion'] ?? 'diaria';
            $granularidad = $validated['granularidad'] ?? 'dia';

            switch ($validated['reporte']) {
                case 'volumen':
                    [$metricas, $kpis] = $this->getMetricasVolumen($fechaDesde, $fechaHasta, $validated, $agrupacion, $granularidad);
                    break;
                case 'crecimiento':
                    [$metricas, $kpis] = $this->getMetricasCrecimiento($fechaDesde, $fechaHasta, $validated, $agrupacion);
                    break;
                case 'actividad':
                    [$metricas, $kpis] = $this->getMetricasActividad($fechaDesde, $fechaHasta);
                    break;
                case 'comportamiento':
                    [$metricas, $kpis] = $this->getMetricasComportamiento();
                    break;
                case 'calidad':
                    [$metricas, $kpis] = $this->getMetricasCalidad();
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'metricas' => $metricas,
                    'kpis' => $kpis,
                    'metadata' => [
                        'fecha_generacion' => now()->toISOString(),
                        'periodo' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
                        'filtros_aplicados' => array_filter($validated),
                        'granularidad' => $granularidad
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en métricas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métricas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * MÉTRICAS DE VOLUMEN CORREGIDAS
     */
    private function getMetricasVolumen($fechaDesde, $fechaHasta, $filtros, $agrupacion, $granularidad)
    {
        // 🔥 CORRECCIÓN 1: Separar stock vs flujo
        $totalUsuariosStock = User::count(); // TOTAL REAL
        $totalUsuariosFlujo = User::whereBetween('created_at', [$fechaDesde, $fechaHasta])->count();
        
        $usuariosActivosStock = User::where('estado', 'activo')->count();
        $usuariosActivosFlujo = User::where('estado', 'activo')
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta])->count();
        
        $metricas = [];

        // Granularidad por tipo de usuario (DISTRIBUCIÓN, no contador)
        if ($granularidad === 'tipo_usuario') {
            $tipos = [
                'usuarios' => 'App\\Models\\Usuario',
                'veterinarios' => 'App\\Models\\Veterinario',
                'administradores' => 'App\\Models\\Administrador'
            ];
            
            foreach ($tipos as $nombre => $clase) {
                $metricas[] = [
                    'id' => "tipo_{$nombre}",
                    'categoria' => $nombre,
                    'valor' => User::where('userable_type', $clase)->count(),
                    'porcentaje' => $totalUsuariosStock > 0 
                        ? round((User::where('userable_type', $clase)->count() / $totalUsuariosStock) * 100, 1)
                        : 0,
                    'tipo_metrica' => 'distribucion'
                ];
            }
        } 
        // Granularidad por ubicación
        elseif ($granularidad === 'ubicacion') {
            $ubicaciones = UbicacionUsuario::select('city', DB::raw('count(*) as total'))
                ->groupBy('city')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get();
            
            foreach ($ubicaciones as $ubicacion) {
                $metricas[] = [
                    'id' => "ubicacion_{$ubicacion->city}",
                    'categoria' => $ubicacion->city,
                    'valor' => $ubicacion->total,
                    'porcentaje' => $totalUsuariosStock > 0 
                        ? round(($ubicacion->total / $totalUsuariosStock) * 100, 2)
                        : 0,
                    'tipo_metrica' => 'distribucion'
                ];
            }
        }
        // Granularidad temporal
        else {
            $groupBy = $this->getGroupByClause($agrupacion);
            
            $metricasRaw = User::select(
                DB::raw("{$groupBy} as fecha"),
                DB::raw('count(*) as total_usuarios'),
                DB::raw("SUM(CASE WHEN userable_type = 'App\\Models\\Usuario' THEN 1 ELSE 0 END) as usuarios"),
                DB::raw("SUM(CASE WHEN userable_type = 'App\\Models\\Veterinario' THEN 1 ELSE 0 END) as veterinarios"),
                DB::raw("SUM(CASE WHEN userable_type = 'App\\Models\\Administrador' THEN 1 ELSE 0 END) as admins"),
                DB::raw("SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos"),
                DB::raw("SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verificados")
            )
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();
            
            $metricas = $metricasRaw->map(function($item) {
                return [
                    'id' => $item->fecha,
                    'fecha' => $item->fecha,
                    'total_usuarios' => $item->total_usuarios,
                    'usuarios' => $item->usuarios,
                    'veterinarios' => $item->veterinarios,
                    'admins' => $item->admins,
                    'activos' => $item->activos,
                    'verificados' => $item->verificados,
                    'tipo_metrica' => 'serie_temporal'
                ];
            })->toArray();
        }

        // 🔥 CORRECCIÓN 2: KPIs con valores correctos
        $kpis = [
            [
                'id' => 1,
                'nombre' => 'Total Usuarios',
                'titulo' => 'Total Usuarios',
                'valor' => $totalUsuariosStock, // STOCK real
                'tendencia' => null,
                'descripcion' => 'Total de usuarios registrados en el sistema (histórico)',
                'tipo' => 'contador',
                'desglose' => [
                    'por_tipo' => [
                        'usuarios' => User::where('userable_type', 'App\\Models\\Usuario')->count(),
                        'veterinarios' => User::where('userable_type', 'App\\Models\\Veterinario')->count(),
                        'administradores' => User::where('userable_type', 'App\\Models\\Administrador')->count()
                    ]
                ]
            ],
            [
                'id' => 2,
                'nombre' => 'Usuarios Nuevos (período)',
                'titulo' => 'Nuevos Usuarios',
                'valor' => $totalUsuariosFlujo, // FLUJO en el período
                'tendencia' => $this->calcularTendencia($fechaDesde, $fechaHasta),
                'descripcion' => "Usuarios registrados entre {$fechaDesde} y {$fechaHasta}",
                'tipo' => 'contador'
            ],
            [
                'id' => 3,
                'nombre' => 'Usuarios Activos',
                'titulo' => 'Activos',
                'valor' => $usuariosActivosStock,
                'tendencia' => null,
                'descripcion' => 'Usuarios con estado activo en el sistema',
                'tipo' => 'contador',
                'desglose' => [
                    'por_tipo' => [
                        'usuarios' => User::where('userable_type', 'App\\Models\\Usuario')->where('estado', 'activo')->count(),
                        'veterinarios' => User::where('userable_type', 'App\\Models\\Veterinario')->where('estado', 'activo')->count()
                    ]
                ]
            ],
            [
                'id' => 4,
                'nombre' => 'Distribución por Tipo',
                'titulo' => 'Distribución',
                'valor' => null, // No es un número, es un objeto
                'tendencia' => null,
                'descripcion' => 'Distribución de usuarios por tipo',
                'tipo' => 'distribucion',
                'datos_distribucion' => [
                    'labels' => ['Usuarios', 'Veterinarios', 'Administradores'],
                    'datasets' => [[
                        'data' => [
                            User::where('userable_type', 'App\\Models\\Usuario')->count(),
                            User::where('userable_type', 'App\\Models\\Veterinario')->count(),
                            User::where('userable_type', 'App\\Models\\Administrador')->count()
                        ],
                        'backgroundColor' => ['#3b82f6', '#10b981', '#8b5cf6']
                    ]]
                ]
            ]
        ];

        return [$metricas, $kpis];
    }

    /**
     * MÉTRICAS DE CRECIMIENTO CORREGIDAS (sin LAG problemático)
     */
    private function getMetricasCrecimiento($fechaDesde, $fechaHasta, $filtros, $agrupacion)
    {
        $groupBy = $this->getGroupByClause($agrupacion);
        
        // 🔥 CORRECCIÓN 3: Obtener datos sin LAG complejo
        $periodos = User::select(
            DB::raw("{$groupBy} as periodo"),
            DB::raw('count(*) as nuevos_usuarios')
        )
        ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
        ->groupBy('periodo')
        ->orderBy('periodo', 'asc')
        ->get();
        
        // Calcular variación manualmente (más seguro que LAG)
        $metricas = [];
        $anterior = null;
        
        foreach ($periodos as $index => $periodo) {
            $variacion = 0;
            if ($anterior !== null && $anterior > 0) {
                $variacion = round((($periodo->nuevos_usuarios - $anterior) / $anterior) * 100, 1);
            }
            
            $metricas[] = [
                'periodo' => $periodo->periodo,
                'nuevos_usuarios' => $periodo->nuevos_usuarios,
                'tasa_crecimiento' => $variacion,
                'variacion' => $variacion,
                'tipo_metrica' => 'serie_temporal'
            ];
            
            $anterior = $periodo->nuevos_usuarios;
        }
        
        $totalNuevos = User::whereBetween('created_at', [$fechaDesde, $fechaHasta])->count();
        
        $kpis = [
            [
                'id' => 1,
                'nombre' => 'Nuevos Usuarios',
                'titulo' => 'Nuevos Registros',
                'valor' => $totalNuevos,
                'tendencia' => $this->calcularTendenciaCrecimiento($fechaDesde, $fechaHasta),
                'descripcion' => 'Total de nuevos usuarios en el período',
                'tipo' => 'contador'
            ],
            [
                'id' => 2,
                'nombre' => 'Promedio Diario',
                'titulo' => 'Promedio/día',
                'valor' => round($totalNuevos / max(1, $this->diasEntreFechas($fechaDesde, $fechaHasta)), 1),
                'tendencia' => null,
                'descripcion' => 'Promedio de nuevos usuarios por día',
                'tipo' => 'contador'
            ]
        ];
        
        return [$metricas, $kpis];
    }

    /**
     * MÉTRICAS DE CALIDAD CORREGIDAS
     */
    private function getMetricasCalidad()
    {
        $totalUsuarios = User::count();
        
        // 🔥 CORRECCIÓN 4: Métricas de calidad como distribución
        $metricas = [
            [
                'categoria' => 'Activos',
                'cantidad' => User::where('estado', 'activo')->count(),
                'porcentaje' => $totalUsuarios > 0 ? round((User::where('estado', 'activo')->count() / $totalUsuarios) * 100, 1) : 0,
                'estado' => 'activo',
                'tipo_metrica' => 'distribucion'
            ],
            [
                'categoria' => 'Inactivos',
                'cantidad' => User::where('estado', 'inactivo')->count(),
                'porcentaje' => $totalUsuarios > 0 ? round((User::where('estado', 'inactivo')->count() / $totalUsuarios) * 100, 1) : 0,
                'estado' => 'inactivo',
                'tipo_metrica' => 'distribucion'
            ],
            [
                'categoria' => 'Bloqueados',
                'cantidad' => User::where('estado', 'bloqueado')->count(),
                'porcentaje' => $totalUsuarios > 0 ? round((User::where('estado', 'bloqueado')->count() / $totalUsuarios) * 100, 1) : 0,
                'estado' => 'bloqueado',
                'tipo_metrica' => 'distribucion'
            ],
            [
                'categoria' => 'Verificados',
                'cantidad' => User::whereNotNull('email_verified_at')->count(),
                'porcentaje' => $totalUsuarios > 0 ? round((User::whereNotNull('email_verified_at')->count() / $totalUsuarios) * 100, 1) : 0,
                'estado' => 'verificado',
                'tipo_metrica' => 'distribucion'
            ]
        ];
        
        $kpis = [
            [
                'id' => 1,
                'nombre' => 'Usuarios Activos',
                'titulo' => 'Activos',
                'valor' => User::where('estado', 'activo')->count(),
                'tendencia' => null,
                'descripcion' => 'Usuarios en estado activo',
                'tipo' => 'contador'
            ],
            [
                'id' => 2,
                'nombre' => 'Tasa Verificación',
                'titulo' => 'Verificación',
                'valor' => $totalUsuarios > 0 ? round((User::whereNotNull('email_verified_at')->count() / $totalUsuarios) * 100, 1) : 0,
                'tendencia' => null,
                'descripcion' => 'Porcentaje de usuarios verificados',
                'tipo' => 'porcentaje'
            ]
        ];
        
        return [$metricas, $kpis];
    }

    /**
     * Helper: Calcular tendencia
     */
    private function calcularTendencia($fechaDesde, $fechaHasta)
    {
        $periodoActual = User::whereBetween('created_at', [$fechaDesde, $fechaHasta])->count();
        $periodoAnteriorInicio = date('Y-m-d', strtotime($fechaDesde . ' -' . $this->diasEntreFechas($fechaDesde, $fechaHasta) . ' days'));
        $periodoAnteriorFin = date('Y-m-d', strtotime($fechaDesde . ' -1 day'));
        
        $periodoAnterior = User::whereBetween('created_at', [$periodoAnteriorInicio, $periodoAnteriorFin])->count();
        
        if ($periodoAnterior > 0) {
            return round((($periodoActual - $periodoAnterior) / $periodoAnterior) * 100, 1);
        }
        
        return 0;
    }
    
    private function calcularTendenciaCrecimiento($fechaDesde, $fechaHasta)
    {
        return $this->calcularTendencia($fechaDesde, $fechaHasta);
    }

    private function getGroupByClause($agrupacion)
    {
        switch ($agrupacion) {
            case 'diaria': return "DATE(created_at)";
            case 'semanal': return "DATE_FORMAT(created_at, '%Y-%u')";
            case 'mensual': return "DATE_FORMAT(created_at, '%Y-%m')";
            case 'trimestral': return "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
            case 'anual': return "YEAR(created_at)";
            default: return "DATE(created_at)";
        }
    }
    
    private function diasEntreFechas($fechaDesde, $fechaHasta)
    {
        $inicio = new \DateTime($fechaDesde);
        $fin = new \DateTime($fechaHasta);
        return $inicio->diff($fin)->days + 1;
    }

    // Métodos auxiliares para otros reportes...
    private function getMetricasActividad($fechaDesde, $fechaHasta) { return [[], []]; }
    private function getMetricasComportamiento() { return [[], []]; }
}