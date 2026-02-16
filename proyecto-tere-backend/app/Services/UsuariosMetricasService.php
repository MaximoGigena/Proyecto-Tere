<?php

namespace App\Services;

use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UsuariosMetricasService
{
    /**
     * Obtener métricas según tipo de reporte
     */
    public function obtenerMetricas(
        string $reporte,
        Carbon $fechaDesde,
        Carbon $fechaHasta,
        string $agrupacion = 'mensual',
        ?string $tipoUsuario = null,
        ?string $estado = null
    ): array {
        $cacheKey = sprintf(
            'metricas_%s_%s_%s_%s_%s',
            $reporte,
            $fechaDesde->format('Ymd'),
            $fechaHasta->format('Ymd'),
            $agrupacion,
            $tipoUsuario ?? 'all'
        );
        
        return Cache::remember($cacheKey, 300, function() use ($reporte, $fechaDesde, $fechaHasta, $agrupacion, $tipoUsuario, $estado) {
            return match($reporte) {
                'volumen' => $this->calcularMetricasVolumen($fechaDesde, $fechaHasta, $agrupacion, $tipoUsuario, $estado),
                'crecimiento' => $this->calcularMetricasCrecimiento($fechaDesde, $fechaHasta, $agrupacion, $tipoUsuario),
                'actividad' => $this->calcularMetricasActividad($fechaDesde, $fechaHasta, $agrupacion, $tipoUsuario, $estado),
                'comportamiento' => $this->calcularMetricasComportamiento($fechaDesde, $fechaHasta, $tipoUsuario),
                'calidad' => $this->calcularMetricasCalidad($fechaDesde, $fechaHasta, $tipoUsuario, $estado),
                default => throw new \InvalidArgumentException("Tipo de reporte no válido: {$reporte}")
            };
        });
    }
    
    /**
     * Métricas de volumen de usuarios - VERSIÓN POSTGRESQL
     */
    
    public function calcularMetricasVolumen(Carbon $fechaDesde, Carbon $fechaHasta, string $agrupacion, ?string $tipoUsuario = null, ?string $estado = null)
    {
        try {
            // SOLUCIÓN: No cargar las relaciones userable con select específico
            // Eliminar el with o hacerlo sin select específico
            $query = User::query()
                ->whereBetween('created_at', [$fechaDesde, $fechaHasta]);
            
            // Filtrar por tipo de usuario
            if ($tipoUsuario) {
                switch($tipoUsuario) {
                    case 'usuario':
                    case 'adoptante':
                        $query->where('userable_type', 'App\Models\Usuario');
                        break;
                    case 'veterinario':
                        $query->where('userable_type', 'App\Models\Veterinario');
                        break;
                    case 'admin':
                    case 'administrador':
                        $query->where('userable_type', 'App\Models\Administrador');
                        break;
                }
            }
            
            // Filtrar por estado
            if ($estado) {
                if ($estado === 'verificado') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($estado === 'no_verificado') {
                    $query->whereNull('email_verified_at');
                } else {
                    $query->where('estado', $estado);
                }
            }
            
            // Obtener solo los datos necesarios de User, no cargar relaciones
            $usuarios = $query->get(['id', 'name', 'email', 'estado', 'email_verified_at', 'userable_type', 'userable_id', 'created_at']);
            
            // Agrupar según la agrupación
            $metricas = $this->agruparUsuariosPorPeriodo($usuarios, $fechaDesde, $fechaHasta, $agrupacion);
            
            // Calcular KPIs
            $kpis = $this->generarKPIsVolumenDesdeUsuarios($usuarios, $fechaDesde, $fechaHasta);
            
            return [
                'metricas' => $metricas,
                'kpis' => $kpis
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en calcularMetricasVolumen: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Agrupar usuarios por período (compatible con PostgreSQL)
     */
    private function agruparUsuariosPorPeriodo($usuarios, Carbon $fechaDesde, Carbon $fechaHasta, string $agrupacion): array
    {
        $periodos = $this->generarRangoFechas($fechaDesde, $fechaHasta, $agrupacion);
        $metricas = [];
        
        foreach ($periodos as $periodo) {
            $usuariosPeriodo = $usuarios->filter(function($user) use ($periodo) {
                return $user->created_at->between($periodo['inicio'], $periodo['fin']);
            });
            
            $metricas[] = [
                'id' => $periodo['id'],
                'fecha' => $periodo['etiqueta'],
                'fecha_raw' => $periodo['inicio']->format('Y-m-d'),
                'total_usuarios' => $usuariosPeriodo->count(),
                'usuarios' => $usuariosPeriodo->where('userable_type', 'App\Models\Usuario')->count(),
                'veterinarios' => $usuariosPeriodo->where('userable_type', 'App\Models\Veterinario')->count(),
                'admins' => $usuariosPeriodo->where('userable_type', 'App\Models\Administrador')->count(),
                'activos' => $usuariosPeriodo->where('estado', 'activo')->count(),
                'verificados' => $usuariosPeriodo->whereNotNull('email_verified_at')->count(),
                'inactivos' => $usuariosPeriodo->where('estado', 'inactivo')->count(),
                'bloqueados' => $usuariosPeriodo->where('estado', 'bloqueado')->count()
            ];
        }
        
        return $metricas;
    }
    
    /**
     * Generar rango de fechas según agrupación
     */
    private function generarRangoFechas(Carbon $fechaDesde, Carbon $fechaHasta, string $agrupacion): array
    {
        $periodos = [];
        $fechaActual = $fechaDesde->copy();
        
        switch($agrupacion) {
            case 'diaria':
                while ($fechaActual <= $fechaHasta) {
                    $periodos[] = [
                        'id' => $fechaActual->format('Ymd'),
                        'etiqueta' => $fechaActual->format('d/m/Y'),
                        'inicio' => $fechaActual->copy()->startOfDay(),
                        'fin' => $fechaActual->copy()->endOfDay()
                    ];
                    $fechaActual->addDay();
                }
                break;
                
            case 'semanal':
                $fechaActual->startOfWeek();
                while ($fechaActual <= $fechaHasta) {
                    $finSemana = $fechaActual->copy()->endOfWeek();
                    if ($finSemana > $fechaHasta) $finSemana = $fechaHasta;
                    
                    $periodos[] = [
                        'id' => $fechaActual->format('YW'),
                        'etiqueta' => 'Sem ' . $fechaActual->weekOfYear . ' ' . $fechaActual->year,
                        'inicio' => $fechaActual->copy(),
                        'fin' => $finSemana
                    ];
                    $fechaActual->addWeek()->startOfWeek();
                }
                break;
                
            case 'mensual':
                $fechaActual->startOfMonth();
                while ($fechaActual <= $fechaHasta) {
                    $finMes = $fechaActual->copy()->endOfMonth();
                    if ($finMes > $fechaHasta) $finMes = $fechaHasta;
                    
                    $periodos[] = [
                        'id' => $fechaActual->format('Ym'),
                        'etiqueta' => $fechaActual->translatedFormat('F Y'),
                        'inicio' => $fechaActual->copy(),
                        'fin' => $finMes
                    ];
                    $fechaActual->addMonth()->startOfMonth();
                }
                break;
                
            case 'trimestral':
                $fechaActual->startOfQuarter();
                while ($fechaActual <= $fechaHasta) {
                    $finTrimestre = $fechaActual->copy()->endOfQuarter();
                    if ($finTrimestre > $fechaHasta) $finTrimestre = $fechaHasta;
                    
                    $periodos[] = [
                        'id' => $fechaActual->format('Y') . 'Q' . ceil($fechaActual->month / 3),
                        'etiqueta' => 'T' . ceil($fechaActual->month / 3) . ' ' . $fechaActual->year,
                        'inicio' => $fechaActual->copy(),
                        'fin' => $finTrimestre
                    ];
                    $fechaActual->addMonths(3)->startOfQuarter();
                }
                break;
                
            case 'anual':
                $fechaActual->startOfYear();
                while ($fechaActual <= $fechaHasta) {
                    $finAnio = $fechaActual->copy()->endOfYear();
                    if ($finAnio > $fechaHasta) $finAnio = $fechaHasta;
                    
                    $periodos[] = [
                        'id' => $fechaActual->format('Y'),
                        'etiqueta' => $fechaActual->format('Y'),
                        'inicio' => $fechaActual->copy(),
                        'fin' => $finAnio
                    ];
                    $fechaActual->addYear()->startOfYear();
                }
                break;
        }
        
        return $periodos;
    }
    
    /**
     * Generar KPIs de volumen desde usuarios
     */
    private function generarKPIsVolumenDesdeUsuarios($usuarios, Carbon $fechaDesde, Carbon $fechaHasta): array
    {
        $total = $usuarios->count();
        
        // Período anterior para comparación
        $diasPeriodo = $fechaDesde->diffInDays($fechaHasta);
        $periodoAnteriorDesde = $fechaDesde->copy()->subDays($diasPeriodo + 1);
        $periodoAnteriorHasta = $fechaDesde->copy()->subDay();
        
        $usuariosAnteriores = User::whereBetween('created_at', [$periodoAnteriorDesde, $periodoAnteriorHasta])->count();
        
        // Calcular crecimiento
        $crecimiento = $usuariosAnteriores > 0 
            ? (($total - $usuariosAnteriores) / $usuariosAnteriores) * 100 
            : ($total > 0 ? 100 : 0);
        
        return [
            [
                'id' => 1,
                'titulo' => 'Total Usuarios',
                'valor' => $total,
                'tendencia' => round($crecimiento, 1),
                'descripcion' => 'Usuarios totales en el período',
                'icono' => 'usuarios',
                'colorClase' => 'bg-blue-50 text-blue-600'
            ],
            [
                'id' => 2,
                'titulo' => 'Adoptantes',
                'valor' => $usuarios->where('userable_type', 'App\Models\Usuario')->count(),
                'tendencia' => round($usuarios->where('userable_type', 'App\Models\Usuario')->count() / max($total, 1) * 100, 1),
                'descripcion' => 'Usuarios registrados como adoptantes',
                'icono' => 'usuarios',
                'colorClase' => 'bg-green-50 text-green-600'
            ],
            [
                'id' => 3,
                'titulo' => 'Veterinarios',
                'valor' => $usuarios->where('userable_type', 'App\Models\Veterinario')->count(),
                'tendencia' => round($usuarios->where('userable_type', 'App\Models\Veterinario')->count() / max($total, 1) * 100, 1),
                'descripcion' => 'Usuarios registrados como veterinarios',
                'icono' => 'calidad',
                'colorClase' => 'bg-purple-50 text-purple-600'
            ],
            [
                'id' => 4,
                'titulo' => 'Usuarios Activos',
                'valor' => $usuarios->where('estado', 'activo')->count(),
                'tendencia' => round($usuarios->where('estado', 'activo')->count() / max($total, 1) * 100, 1),
                'descripcion' => 'Porcentaje de usuarios activos',
                'icono' => 'actividad',
                'colorClase' => 'bg-orange-50 text-orange-600'
            ]
        ];
    }
    
    /**
     * Métricas de crecimiento
     */
    private function calcularMetricasCrecimiento(
        Carbon $fechaDesde,
        Carbon $fechaHasta,
        string $agrupacion,
        ?string $tipoUsuario
    ): array {
        $metricas = [];
        $periodos = $this->generarRangoFechas($fechaDesde, $fechaHasta, $agrupacion);
        
        foreach ($periodos as $index => $periodo) {
            $query = User::query();
            
            // Filtrar por tipo de usuario
            if ($tipoUsuario) {
                $query->where('userable_type', $this->obtenerTipoUsuarioModelo($tipoUsuario));
            }
            
            $nuevosUsuarios = $query->whereBetween('created_at', [$periodo['inicio'], $periodo['fin']])->count();
            
            // Calcular crecimiento comparativo
            $tasaCrecimiento = 0;
            if ($index > 0) {
                $periodoAnterior = $periodos[$index - 1];
                $usuariosAnteriores = (clone $query)->whereBetween('created_at', [$periodoAnterior['inicio'], $periodoAnterior['fin']])->count();
                
                if ($usuariosAnteriores > 0) {
                    $tasaCrecimiento = (($nuevosUsuarios - $usuariosAnteriores) / $usuariosAnteriores) * 100;
                }
            }
            
            $metricas[] = [
                'id' => $periodo['id'],
                'periodo' => $periodo['etiqueta'],
                'nuevos_usuarios' => $nuevosUsuarios,
                'tasa_crecimiento' => round($tasaCrecimiento, 2),
                'periodo_anterior' => $index > 0 ? $periodos[$index - 1]['etiqueta'] : 'N/A',
                'variacion' => round($tasaCrecimiento, 2)
            ];
        }
        
        return [
            'metricas' => $metricas,
            'kpis' => $this->generarKPIsCrecimiento($metricas)
        ];
    }
    
    /**
     * Métricas de actividad
     */
    private function calcularMetricasActividad(
        Carbon $fechaDesde,
        Carbon $fechaHasta,
        string $agrupacion,
        ?string $tipoUsuario,
        ?string $estado
    ): array {
        // Por ahora, datos simulados - implementar según tu lógica de actividad
        $periodos = $this->generarRangoFechas($fechaDesde, $fechaHasta, 'diaria');
        $metricas = [];
        
        foreach ($periodos as $periodo) {
            $query = User::query();
            
            if ($tipoUsuario) {
                $query->where('userable_type', $this->obtenerTipoUsuarioModelo($tipoUsuario));
            }
            
            if ($estado) {
                if ($estado === 'verificado') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($estado === 'no_verificado') {
                    $query->whereNull('email_verified_at');
                } else {
                    $query->where('estado', $estado);
                }
            }
            
            // DAU (simulado) - implementar según tu lógica
            $dau = $query->whereBetween('last_login_at', [$periodo['inicio'], $periodo['fin']])->count();
            $mau = $query->whereBetween('last_login_at', [$periodo['inicio']->copy()->subDays(30), $periodo['fin']])->count();
            $ratioDauMau = $mau > 0 ? ($dau / $mau) * 100 : 0;
            
            $metricas[] = [
                'id' => $periodo['id'],
                'fecha' => $periodo['inicio']->format('Y-m-d'),
                'dau' => $dau,
                'mau' => $mau,
                'ratio_dau_mau' => round($ratioDauMau, 2),
                'inactivos_30d' => $query->where('last_login_at', '<', $periodo['inicio']->copy()->subDays(30))->count(),
                'acciones_promedio' => rand(5, 15) // Implementar según tu lógica
            ];
        }
        
        return [
            'metricas' => $metricas,
            'kpis' => $this->generarKPIsActividad($metricas)
        ];
    }
    
    /**
     * Métricas de comportamiento
     */
    private function calcularMetricasComportamiento(
        Carbon $fechaDesde,
        Carbon $fechaHasta,
        ?string $tipoUsuario
    ): array {
        // Datos simulados para comportamiento
        $segmentos = $tipoUsuario 
            ? [$tipoUsuario => $this->obtenerNombreSegmento($tipoUsuario)]
            : [
                'adoptante' => 'Adoptantes',
                'veterinario' => 'Veterinarios',
                'admin' => 'Administradores'
            ];
        
        $metricas = [];
        
        foreach ($segmentos as $tipo => $nombre) {
            $metricas[] = [
                'id' => crc32($tipo),
                'segmento' => $nombre,
                'publican_mascotas' => rand(20, 70),
                'perfil_completo' => rand(50, 90),
                'llegan_adopcion' => rand(10, 40),
                'funnel_registro' => rand(70, 95),
                'funnel_adopcion' => rand(5, 20)
            ];
        }
        
        return [
            'metricas' => $metricas,
            'kpis' => $this->generarKPIsComportamiento($metricas)
        ];
    }
    
    /**
     * Métricas de calidad
     */
    private function calcularMetricasCalidad(
        Carbon $fechaDesde,
        Carbon $fechaHasta,
        ?string $tipoUsuario,
        ?string $estado
    ): array {
        // Datos de calidad según tu modelo
        $categorias = [
            ['id' => 1, 'nombre' => 'Bloqueados', 'model' => User::class, 'estado' => 'bloqueado'],
            ['id' => 2, 'nombre' => 'Veterinarios Pendientes', 'model' => Veterinario::class, 'estado' => 'pendiente'],
            ['id' => 3, 'nombre' => 'No Verificados', 'model' => User::class, 'campo' => 'email_verified_at', 'valor' => null],
            ['id' => 4, 'nombre' => 'Inactivos', 'model' => User::class, 'estado' => 'inactivo'],
        ];
        
        $metricas = [];
        $totalUsuarios = User::when($tipoUsuario, function($q) use ($tipoUsuario) {
            $q->where('userable_type', $this->obtenerTipoUsuarioModelo($tipoUsuario));
        })->count();
        
        foreach ($categorias as $categoria) {
            $cantidad = 0;
            
            if ($categoria['model'] === User::class) {
                $query = User::query();
                
                if ($tipoUsuario) {
                    $query->where('userable_type', $this->obtenerTipoUsuarioModelo($tipoUsuario));
                }
                
                if (isset($categoria['estado'])) {
                    $cantidad = $query->where('estado', $categoria['estado'])->count();
                } elseif (isset($categoria['campo'])) {
                    $cantidad = $query->whereNull($categoria['campo'])->count();
                }
            } elseif ($categoria['model'] === Veterinario::class) {
                $cantidad = Veterinario::where('estado', 'pendiente')->count();
            }
            
            $porcentaje = $totalUsuarios > 0 ? ($cantidad / $totalUsuarios) * 100 : 0;
            $estadoCalidad = $this->determinarEstadoCalidad($porcentaje);
            
            $metricas[] = [
                'id' => $categoria['id'],
                'categoria' => $categoria['nombre'],
                'cantidad' => $cantidad,
                'porcentaje' => round($porcentaje, 2),
                'tendencia' => rand(-10, 10), // Implementar según tu lógica
                'estado' => $estadoCalidad
            ];
        }
        
        return [
            'metricas' => $metricas,
            'kpis' => $this->generarKPIsCalidad($metricas)
        ];
    }
    
    /**
     * Métodos auxiliares
     */
    private function obtenerTipoUsuarioModelo(?string $tipoUsuario): ?string
    {
        return match($tipoUsuario) {
            'usuario', 'adoptante' => 'App\Models\Usuario',
            'veterinario' => 'App\Models\Veterinario',
            'admin', 'administrador' => 'App\Models\Administrador',
            default => null
        };
    }
    
    private function obtenerNombreSegmento(string $tipoUsuario): string
    {
        return match($tipoUsuario) {
            'usuario', 'adoptante' => 'Adoptantes',
            'veterinario' => 'Veterinarios',
            'admin' => 'Administradores',
            default => 'Usuarios'
        };
    }
    
    private function determinarEstadoCalidad(float $porcentaje): string
    {
        if ($porcentaje > 10) return 'critico';
        if ($porcentaje > 5) return 'alerta';
        if ($porcentaje > 2) return 'estable';
        return 'bueno';
    }
    
    /**
     * Métodos para generar KPIs
     */
    private function generarKPIsVolumen(array $metricas): array
    {
        if (empty($metricas)) {
            return [];
        }
        
        $totalPeriodo = array_sum(array_column($metricas, 'total_usuarios'));
        $promedioUsuarios = $totalPeriodo / count($metricas);
        $tendencia = $this->calcularTendencia($metricas, 'total_usuarios');
        
        return [
            [
                'id' => 1,
                'titulo' => 'Total Usuarios',
                'valor' => round($promedioUsuarios),
                'tendencia' => $tendencia,
                'descripcion' => 'Promedio del período',
                'icono' => 'usuarios',
                'colorClase' => 'bg-blue-50 text-blue-600'
            ],
            [
                'id' => 2,
                'titulo' => 'Usuarios Activos',
                'valor' => round(array_sum(array_column($metricas, 'activos')) / count($metricas)),
                'tendencia' => $this->calcularTendencia($metricas, 'activos'),
                'descripcion' => 'Promedio del período',
                'icono' => 'actividad',
                'colorClase' => 'bg-green-50 text-green-600'
            ],
            [
                'id' => 3,
                'titulo' => 'Verificados',
                'valor' => round(array_sum(array_column($metricas, 'verificados')) / count($metricas)),
                'tendencia' => $this->calcularTendencia($metricas, 'verificados'),
                'descripcion' => 'Promedio del período',
                'icono' => 'calidad',
                'colorClase' => 'bg-purple-50 text-purple-600'
            ],
            [
                'id' => 4,
                'titulo' => 'Adoptantes',
                'valor' => round(array_sum(array_column($metricas, 'usuarios')) / count($metricas)),
                'tendencia' => $this->calcularTendencia($metricas, 'usuarios'),
                'descripcion' => 'Segmento principal',
                'icono' => 'usuarios',
                'colorClase' => 'bg-orange-50 text-orange-600'
            ]
        ];
    }
    
    private function generarKPIsCrecimiento(array $metricas): array
    {
        if (empty($metricas)) {
            return [];
        }
        
        $ultimoPeriodo = end($metricas);
        
        return [
            [
                'id' => 1,
                'titulo' => 'Nuevos Usuarios',
                'valor' => $ultimoPeriodo['nuevos_usuarios'],
                'tendencia' => $ultimoPeriodo['tasa_crecimiento'],
                'descripcion' => 'Total del período',
                'icono' => 'crecimiento',
                'colorClase' => 'bg-blue-50 text-blue-600'
            ],
            [
                'id' => 2,
                'titulo' => 'Tasa Crecimiento',
                'valor' => $ultimoPeriodo['tasa_crecimiento'],
                'tendencia' => $ultimoPeriodo['variacion'],
                'descripcion' => 'Último período',
                'icono' => 'crecimiento',
                'colorClase' => 'bg-green-50 text-green-600'
            ],
            [
                'id' => 3,
                'titulo' => 'Variación',
                'valor' => $ultimoPeriodo['variacion'],
                'tendencia' => null,
                'descripcion' => 'vs período anterior',
                'icono' => 'crecimiento',
                'colorClase' => 'bg-purple-50 text-purple-600'
            ],
            [
                'id' => 4,
                'titulo' => 'Período Anterior',
                'valor' => $ultimoPeriodo['periodo_anterior'],
                'tendencia' => null,
                'descripcion' => 'Comparativa',
                'icono' => 'crecimiento',
                'colorClase' => 'bg-orange-50 text-orange-600'
            ]
        ];
    }
    
    private function generarKPIsActividad(array $metricas): array
    {
        if (empty($metricas)) {
            return [];
        }
        
        $ultimoPeriodo = end($metricas);
        
        return [
            [
                'id' => 1,
                'titulo' => 'DAU',
                'valor' => $ultimoPeriodo['dau'],
                'tendencia' => $this->calcularTendencia($metricas, 'dau'),
                'descripcion' => 'Usuarios activos diarios',
                'icono' => 'actividad',
                'colorClase' => 'bg-blue-50 text-blue-600'
            ],
            [
                'id' => 2,
                'titulo' => 'MAU',
                'valor' => $ultimoPeriodo['mau'],
                'tendencia' => $this->calcularTendencia($metricas, 'mau'),
                'descripcion' => 'Usuarios activos mensuales',
                'icono' => 'actividad',
                'colorClase' => 'bg-green-50 text-green-600'
            ],
            [
                'id' => 3,
                'titulo' => 'Ratio DAU/MAU',
                'valor' => $ultimoPeriodo['ratio_dau_mau'],
                'tendencia' => $this->calcularTendencia($metricas, 'ratio_dau_mau'),
                'descripcion' => 'Engagement del usuario',
                'icono' => 'actividad',
                'colorClase' => 'bg-purple-50 text-purple-600'
            ],
            [
                'id' => 4,
                'titulo' => 'Acciones/Promedio',
                'valor' => $ultimoPeriodo['acciones_promedio'],
                'tendencia' => $this->calcularTendencia($metricas, 'acciones_promedio'),
                'descripcion' => 'Por usuario activo',
                'icono' => 'actividad',
                'colorClase' => 'bg-orange-50 text-orange-600'
            ]
        ];
    }
    
    private function generarKPIsComportamiento(array $metricas): array
    {
        if (empty($metricas)) {
            return [];
        }
        
        $adoptantes = array_filter($metricas, fn($m) => $m['segmento'] === 'Adoptantes');
        $adoptantes = reset($adoptantes) ?: $metricas[0];
        
        return [
            [
                'id' => 1,
                'titulo' => 'Publican Mascotas',
                'valor' => $adoptantes['publican_mascotas'],
                'tendencia' => rand(-5, 10),
                'descripcion' => '% de usuarios',
                'icono' => 'comportamiento',
                'colorClase' => 'bg-blue-50 text-blue-600'
            ],
            [
                'id' => 2,
                'titulo' => 'Perfil Completo',
                'valor' => $adoptantes['perfil_completo'],
                'tendencia' => rand(0, 15),
                'descripcion' => '% de usuarios',
                'icono' => 'comportamiento',
                'colorClase' => 'bg-green-50 text-green-600'
            ],
            [
                'id' => 3,
                'titulo' => 'Llegan a Adopción',
                'valor' => $adoptantes['llegan_adopcion'],
                'tendencia' => rand(5, 20),
                'descripcion' => 'Conversión final',
                'icono' => 'comportamiento',
                'colorClase' => 'bg-purple-50 text-purple-600'
            ],
            [
                'id' => 4,
                'titulo' => 'Funnel Registro',
                'valor' => $adoptantes['funnel_registro'],
                'tendencia' => rand(-2, 5),
                'descripcion' => '% completan registro',
                'icono' => 'comportamiento',
                'colorClase' => 'bg-orange-50 text-orange-600'
            ]
        ];
    }
    
     private function generarKPIsCalidad(array $metricas): array
    {
        if (empty($metricas)) return [];
        
        $kpis = [];
        $id = 1;
        
        foreach ($metricas as $metrica) {
            $kpis[] = [
                'id' => $id++,
                'titulo' => $metrica['categoria'],
                'valor' => $metrica['cantidad'],
                'tendencia' => $metrica['tendencia'],
                'descripcion' => $this->obtenerDescripcionCalidad($metrica['categoria']),
                'icono' => 'calidad',
                'colorClase' => $this->obtenerClaseColorCalidad($metrica['estado'])
            ];
        }
        
        return $kpis;
    }
    
    private function calcularTendencia(array $metricas, string $campo): float
    {
        if (count($metricas) < 2) return 0;
        
        $mitad = floor(count($metricas) / 2);
        $primera = array_slice($metricas, 0, $mitad);
        $segunda = array_slice($metricas, $mitad);
        
        $promedio1 = array_sum(array_column($primera, $campo)) / count($primera);
        $promedio2 = array_sum(array_column($segunda, $campo)) / count($segunda);
        
        if ($promedio1 == 0) return 0;
        
        return round((($promedio2 - $promedio1) / $promedio1) * 100, 1);
    }
    
    private function obtenerDescripcionCalidad(string $categoria): string
    {
        return match($categoria) {
            'Bloqueados' => 'Control de seguridad',
            'Veterinarios Pendientes' => 'Verificación pendiente',
            'No Verificados' => 'Email no verificado',
            'Inactivos' => 'Sin actividad reciente',
            default => 'Indicador de calidad'
        };
    }
    
    private function obtenerClaseColorCalidad(string $estado): string
    {
        return match($estado) {
            'critico' => 'bg-red-50 text-red-600',
            'alerta' => 'bg-yellow-50 text-yellow-600',
            'estable' => 'bg-blue-50 text-blue-600',
            default => 'bg-green-50 text-green-600'
        };
    }

    
    /**
     * Métodos públicos adicionales para otras funcionalidades del controller
     */
    public function calcularTendencias(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        // Implementa cálculo de tendencias
        return [];
    }
    
    public function obtenerMetricasPorUsuario(int $userId, string $periodo): array
    {
        // Implementa métricas específicas del usuario
        return [];
    }
    
    public function compararPeriodos(string $periodoActual, string $periodoComparacion): array
    {
        // Implementa comparativa
        return [];
    }
    
    public function generarAlertas(): array
    {
        // Implementa sistema de alertas
        return [];
    }
    
    public function obtenerResumenDashboard(): array
    {
        // Implementa resumen para dashboard
        return [];
    }
    
    public function obtenerDistribucionUsuarios(): array
    {
        // Implementa distribución de usuarios
        return [];
    }
    
    public function obtenerKPIsVolumen(Carbon $inicio, Carbon $fin): array
    {
        $metricas = $this->calcularMetricasVolumen($inicio, $fin, 'mensual', null, null);
        return $metricas['kpis'] ?? [];
    }
    
    public function obtenerKPIsActividad(Carbon $inicio, Carbon $fin): array
    {
        $metricas = $this->calcularMetricasActividad($inicio, $fin, 'diaria', null, null);
        return $metricas['kpis'] ?? [];
    }
    
    public function obtenerKPIsCalidad(): array
    {
        $metricas = $this->calcularMetricasCalidad(Carbon::now()->subMonth(), Carbon::now(), null, null);
        return $metricas['kpis'] ?? [];
    }
}