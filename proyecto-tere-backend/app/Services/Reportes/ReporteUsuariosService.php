<?php
// app/Services/Reportes/ReporteUsuariosService.php

namespace App\Services\Reportes;

use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\UbicacionUsuario;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteUsuariosService extends BaseReporteService
{

    public function setParametros(array $parametros): self
    {
        $this->parametros = array_merge($this->parametros ?? [], $parametros);
        return $this;
    }

    public function ejecutar(): array
    {
        $startTime = microtime(true);
        
        $metricas = $this->parametros['metricas'] ?? ['total_usuarios', 'usuarios_activos', 'nuevos_usuarios'];
        
        $this->resultados = [
            'metricas' => [],
            'segmentacion' => [],
            'tendencias' => [],
            'resumen' => [],
            'metadatos' => [
                'fecha_inicio' => $this->parametros['fecha_inicio'] ?? null,
                'fecha_fin' => $this->parametros['fecha_fin'] ?? null,
                'total_metricas' => count($metricas)
            ]
        ];

        // Calcular métricas seleccionadas
        foreach ($metricas as $metrica) {
            $this->resultados['metricas'][$metrica] = $this->calcularMetrica($metrica);
        }

        // Aplicar segmentación si está configurada
        if (isset($this->parametros['segmentacion'])) {
            $this->resultados['segmentacion'] = $this->aplicarSegmentacion($this->parametros['segmentacion']);
        }

        // Calcular comparaciones si se solicita
        if (isset($this->parametros['comparacion_periodo'])) {
            $this->resultados['comparacion'] = $this->calcularComparacionPeriodo();
        }

        
        // Calcular tiempo de ejecución
        $this->resultados['metadatos']['tiempo_ejecucion'] = round(microtime(true) - $startTime, 3);

        return $this->resultados;
    }

    private function calcularMetrica(string $metrica): array
    {
        switch ($metrica) {
            case 'total_usuarios':
                return $this->calcularTotalUsuarios();
                
            case 'usuarios_activos':
                return $this->calcularUsuariosActivos();
                
            case 'nuevos_usuarios':
                return $this->calcularNuevosUsuarios();
                
            case 'tipo_usuario':
                return $this->calcularDistribucionTipoUsuario();
                
            case 'ubicaciones':
                return $this->calcularUbicacionesUsuarios();
                
            case 'registro_por_fuente':
                return $this->calcularRegistroPorFuente();
                
            case 'edad_promedio':
                return $this->calcularEdadPromedio();
                
            case 'usuarios_sancionados':
                return $this->calcularUsuariosSancionados();
                
            case 'retencion':
                return $this->calcularRetencionUsuarios();
                
            default:
                return [
                    'valor' => 0,
                    'etiqueta' => 'Métrica no válida',
                    'tipo' => 'error',
                    'error' => 'Métrica no soportada'
                ];
        }
    }

    private function calcularTotalUsuarios(): array
    {
        $query = User::query();
        
        $this->aplicarFiltrosFecha($query);
        
        $total = $query->count();
        
        // Obtener total por tipo de usuario
        $porTipo = User::select('userable_type', DB::raw('COUNT(*) as total'))
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('userable_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $tipo = str_replace('App\\Models\\', '', $item->userable_type);
                return [$tipo => $item->total];
            });

        return [
            'valor' => $total,
            'etiqueta' => 'Total de Usuarios',
            'tipo' => 'contador',
            'detalle' => $porTipo,
            'icono' => '👥'
        ];
    }

    private function calcularUsuariosActivos(): array
    {
        // Usuarios normales activos
        $usuariosActivos = User::whereHas('userable', function ($query) {
                $query->where('activo', true);
            })
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        // Veterinarios aprobados y activos
        $veterinariosActivos = User::whereHasMorph('userable', [Veterinario::class], function ($query) {
                $query->where('activo', true)
                      ->where('estado', Veterinario::ESTADO_APROBADO);
            })
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        $totalActivos = $usuariosActivos + $veterinariosActivos;

        return [
            'valor' => $totalActivos,
            'etiqueta' => 'Usuarios Activos',
            'tipo' => 'contador',
            'detalle' => [
                'usuarios' => $usuariosActivos,
                'veterinarios' => $veterinariosActivos
            ],
            'icono' => '✅'
        ];
    }

    private function calcularNuevosUsuarios(): array
    {
        $query = User::query();
        $this->aplicarFiltrosFecha($query, 'created_at');
        
        $nuevos = $query->count();
        
        // Obtener tendencia diaria/semanal
        $intervalo = isset($this->parametros['agrupar_por']) ? $this->parametros['agrupar_por'] : 'diario';
        
        $tendencia = User::select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as total')
            )
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return [
            'valor' => $nuevos,
            'etiqueta' => 'Nuevos Usuarios',
            'tipo' => 'contador',
            'tendencia' => $tendencia,
            'icono' => '🆕'
        ];
    }

    private function calcularDistribucionTipoUsuario(): array
    {
        $distribucion = User::select(
                'userable_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users), 2) as porcentaje')
            )
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('userable_type')
            ->get()
            ->map(function ($item) {
                $tipo = str_replace('App\\Models\\', '', $item->userable_type);
                return [
                    'tipo' => $tipo,
                    'total' => $item->total,
                    'porcentaje' => (float) $item->porcentaje
                ];
            });

        return [
            'valor' => $distribucion->sum('total'),
            'etiqueta' => 'Distribución por Tipo',
            'tipo' => 'distribucion',
            'datos' => $distribucion,
            'icono' => '📊'
        ];
    }

    private function calcularUbicacionesUsuarios(): array
    {
        $ubicaciones = UbicacionUsuario::select(
                'city',
                'state',
                'country',
                DB::raw('COUNT(DISTINCT user_id) as total_usuarios')
            )
            ->whereNotNull('city')
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('location_updated_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('location_updated_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('city', 'state', 'country')
            ->orderBy('total_usuarios', 'desc')
            ->limit(20)
            ->get();

        $totalConUbicacion = UbicacionUsuario::select(DB::raw('COUNT(DISTINCT user_id) as total'))
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('location_updated_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('location_updated_at', '<=', $this->parametros['fecha_fin']);
            })
            ->first()
            ->total;

        $totalUsuarios = User::when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        $porcentajeConUbicacion = $totalUsuarios > 0 
            ? round(($totalConUbicacion / $totalUsuarios) * 100, 2)
            : 0;

        return [
            'valor' => $totalConUbicacion,
            'etiqueta' => 'Usuarios con Ubicación',
            'tipo' => 'geografico',
            'datos' => $ubicaciones,
            'estadisticas' => [
                'total_usuarios' => $totalUsuarios,
                'con_ubicacion' => $totalConUbicacion,
                'porcentaje_con_ubicacion' => $porcentajeConUbicacion
            ],
            'icono' => '📍'
        ];
    }

    private function calcularRegistroPorFuente(): array
    {
        $registros = User::select(
                DB::raw('CASE 
                    WHEN google_id IS NOT NULL THEN "google"
                    WHEN facebook_id IS NOT NULL THEN "facebook"
                    ELSE "email"
                END as fuente'),
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users), 2) as porcentaje')
            )
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('fuente')
            ->get();

        return [
            'valor' => $registros->sum('total'),
            'etiqueta' => 'Registro por Fuente',
            'tipo' => 'distribucion',
            'datos' => $registros,
            'icono' => '🔗'
        ];
    }

    private function calcularEdadPromedio(): array
    {
        // Para usuarios normales (desde el modelo Usuario)
        $edadPromedio = Usuario::select(DB::raw('AVG(edad) as promedio'))
            ->whereNotNull('edad')
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereHas('user', function ($subq) {
                    $subq->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
                });
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereHas('user', function ($subq) {
                    $subq->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
                });
            })
            ->first()
            ->promedio;

        // Distribución por grupos de edad
        $gruposEdad = Usuario::select(
                DB::raw('CASE
                    WHEN edad < 18 THEN "Menor de 18"
                    WHEN edad BETWEEN 18 AND 25 THEN "18-25"
                    WHEN edad BETWEEN 26 AND 35 THEN "26-35"
                    WHEN edad BETWEEN 36 AND 50 THEN "36-50"
                    WHEN edad > 50 THEN "Mayor de 50"
                    ELSE "No especificado"
                END as grupo_edad'),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('edad')
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereHas('user', function ($subq) {
                    $subq->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
                });
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereHas('user', function ($subq) {
                    $subq->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
                });
            })
            ->groupBy('grupo_edad')
            ->get();

        return [
            'valor' => round($edadPromedio ?? 0, 1),
            'etiqueta' => 'Edad Promedio',
            'tipo' => 'estadistico',
            'datos' => $gruposEdad,
            'icono' => '🎂'
        ];
    }

    private function calcularUsuariosSancionados(): array
    {
        // Usar el método que ya existe en el modelo User
        $usuariosSancionados = User::where('estado', 'suspendido')
            ->orWhere('estado', 'bloqueado')
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        $totalUsuarios = User::when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        $porcentajeSancionados = $totalUsuarios > 0 
            ? round(($usuariosSancionados / $totalUsuarios) * 100, 2)
            : 0;

        return [
            'valor' => $usuariosSancionados,
            'etiqueta' => 'Usuarios Sancionados',
            'tipo' => 'contador',
            'estadisticas' => [
                'total_usuarios' => $totalUsuarios,
                'porcentaje_sancionados' => $porcentajeSancionados
            ],
            'icono' => '⚠️'
        ];
    }

    private function calcularRetencionUsuarios(): array
    {
        // Esta métrica es más compleja y requeriría tracking de actividad
        // Por ahora, calculamos usuarios que han tenido actividad reciente
        $usuariosActivosRecientes = User::whereHas('ubicaciones', function ($query) {
                $query->where('location_updated_at', '>=', now()->subDays(30));
            })
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        $totalUsuariosPeriodo = User::when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->count();

        $tasaRetencion = $totalUsuariosPeriodo > 0 
            ? round(($usuariosActivosRecientes / $totalUsuariosPeriodo) * 100, 2)
            : 0;

        return [
            'valor' => $tasaRetencion,
            'etiqueta' => 'Tasa de Retención (30 días)',
            'tipo' => 'porcentaje',
            'detalle' => [
                'usuarios_activos' => $usuariosActivosRecientes,
                'total_usuarios' => $totalUsuariosPeriodo
            ],
            'icono' => '📈'
        ];
    }

    private function aplicarSegmentacion(string $campo): array
    {
        switch ($campo) {
            case 'tipo_usuario':
                return $this->segmentarPorTipoUsuario();
                
            case 'fuente_registro':
                return $this->segmentarPorFuenteRegistro();
                
            case 'ubicacion':
                return $this->segmentarPorUbicacion();
                
            case 'edad':
                return $this->segmentarPorEdad();
                
            case 'estado':
                return $this->segmentarPorEstado();
                
            default:
                return ['error' => 'Segmentación no válida'];
        }
    }


    private function segmentarPorTipoUsuario(): array
    {
        return User::select(
                'userable_type',
                DB::raw('COUNT(*) as total')
            )
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('userable_type')
            ->get()
            ->map(function ($item) {
                $tipo = str_replace('App\\Models\\', '', $item->userable_type);
                return [
                    'segmento' => $tipo,
                    'valor' => $item->total
                ];
            })
            ->toArray();
    }

    private function segmentarPorFuenteRegistro(): array
    {
        return User::select(
                DB::raw('CASE 
                    WHEN google_id IS NOT NULL THEN "google"
                    WHEN facebook_id IS NOT NULL THEN "facebook"
                    ELSE "email"
                END as fuente'),
                DB::raw('COUNT(*) as total')
            )
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('fuente')
            ->get()
            ->map(function ($item) {
                return [
                    'segmento' => $item->fuente,
                    'valor' => $item->total
                ];
            })
            ->toArray();
    }

    private function segmentarPorUbicacion(): array
    {
        return UbicacionUsuario::select(
                'city',
                DB::raw('COUNT(DISTINCT user_id) as total')
            )
            ->whereNotNull('city')
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('location_updated_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('location_updated_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('city')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'segmento' => $item->city ?: 'Sin ciudad',
                    'valor' => $item->total
                ];
            })
            ->toArray();
    }

    private function segmentarPorEdad(): array
    {
        return Usuario::select(
                DB::raw('CASE
                    WHEN edad < 18 THEN "Menor de 18"
                    WHEN edad BETWEEN 18 AND 25 THEN "18-25"
                    WHEN edad BETWEEN 26 AND 35 THEN "26-35"
                    WHEN edad BETWEEN 36 AND 50 THEN "36-50"
                    WHEN edad > 50 THEN "Mayor de 50"
                    ELSE "No especificado"
                END as grupo_edad'),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('edad')
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereHas('user', function ($subq) {
                    $subq->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
                });
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereHas('user', function ($subq) {
                    $subq->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
                });
            })
            ->groupBy('grupo_edad')
            ->get()
            ->map(function ($item) {
                return [
                    'segmento' => $item->grupo_edad,
                    'valor' => $item->total
                ];
            })
            ->toArray();
    }

    private function segmentarPorEstado(): array
    {
        return User::select(
                'estado',
                DB::raw('COUNT(*) as total')
            )
            ->when(isset($this->parametros['fecha_inicio']), function ($q) {
                $q->whereDate('created_at', '>=', $this->parametros['fecha_inicio']);
            })
            ->when(isset($this->parametros['fecha_fin']), function ($q) {
                $q->whereDate('created_at', '<=', $this->parametros['fecha_fin']);
            })
            ->groupBy('estado')
            ->get()
            ->map(function ($item) {
                return [
                    'segmento' => $item->estado ?: 'activo',
                    'valor' => $item->total
                ];
            })
            ->toArray();
    }

    private function calcularComparacionPeriodo(): array
    {
        $fechaInicio = $this->parametros['fecha_inicio'] ?? now()->subMonth()->toDateString();
        $fechaFin = $this->parametros['fecha_fin'] ?? now()->toDateString();
        
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        $duracion = $inicio->diffInDays($fin);
        
        $inicioAnterior = $inicio->copy()->subDays($duracion);
        $finAnterior = $inicio->copy()->subDay();
        
        return [
            'periodo_actual' => [
                'inicio' => $inicio->toDateString(),
                'fin' => $fin->toDateString(),
                'dias' => $duracion
            ],
            'periodo_anterior' => [
                'inicio' => $inicioAnterior->toDateString(),
                'fin' => $finAnterior->toDateString(),
                'dias' => $duracion
            ]
        ];
    }
}