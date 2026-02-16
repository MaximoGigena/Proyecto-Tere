<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UsuariosMetricasService;
use App\Exports\UsuarioMetricasExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UsuarioMetricasController extends Controller
{
    protected $metricasService;
    
    public function __construct(UsuariosMetricasService $metricasService)
    {
        $this->metricasService = $metricasService;
    }
    
    public function obtenerMetricas(Request $request)
    {
        try {
            // Validar entrada
            $validated = $request->validate([
                'reporte' => 'required|string|in:volumen,crecimiento,actividad,comportamiento,calidad',
                'fecha_desde' => 'nullable|date|before_or_equal:fecha_hasta',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'tipo_usuario' => 'nullable|string|in:adoptante,veterinario,admin,ong,usuario',
                'estado' => 'nullable|string|in:activo,inactivo,bloqueado,verificado,no_verificado',
                'agrupacion' => 'nullable|string|in:diaria,semanal,mensual,trimestral,anual',
                'limit' => 'nullable|integer|min:1|max:1000',
                'page' => 'nullable|integer|min:1'
            ]);
            
            // Procesar fechas
            $fechaDesde = $request->filled('fecha_desde') 
                ? Carbon::parse($request->fecha_desde)->startOfDay()
                : Carbon::now()->subMonth()->startOfDay();
                
            $fechaHasta = $request->filled('fecha_hasta')
                ? Carbon::parse($request->fecha_hasta)->endOfDay()
                : Carbon::now()->endOfDay();
            
            // Obtener métricas usando el método público
            $resultado = $this->metricasService->obtenerMetricas(
                $request->reporte,
                $fechaDesde,
                $fechaHasta,
                $request->agrupacion ?? 'mensual',
                $request->tipo_usuario,
                $request->estado
            );
            
            // Paginación
            $limit = $request->limit ?? 100;
            $page = $request->page ?? 1;
            $total = count($resultado['metricas'] ?? []);
            $totalPaginas = $limit > 0 ? ceil($total / $limit) : 1;
            
            $metricasPaginadas = $limit > 0 
                ? array_slice($resultado['metricas'] ?? [], ($page - 1) * $limit, $limit)
                : $resultado['metricas'] ?? [];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'metricas' => $metricasPaginadas,
                    'kpis' => $resultado['kpis'] ?? [],
                    'total' => $total,
                    'pagina_actual' => (int)$page,
                    'total_paginas' => $totalPaginas,
                    'filtros' => [
                        'reporte' => $request->reporte,
                        'fecha_desde' => $fechaDesde->format('Y-m-d'),
                        'fecha_hasta' => $fechaHasta->format('Y-m-d'),
                        'tipo_usuario' => $request->tipo_usuario,
                        'estado' => $request->estado,
                        'agrupacion' => $request->agrupacion ?? 'mensual'
                    ]
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error en UsuarioMetricasController@obtenerMetricas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métricas de usuarios',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    // ... resto de métodos manteniendo las mismas correcciones ...
    
    public function obtenerDashboard(Request $request)
    {
        try {
            // Obtener todos los tipos de métricas para el dashboard
            $hoy = Carbon::now();
            $inicioMes = $hoy->copy()->startOfMonth();
            
            // Usar los métodos públicos disponibles
            $dashboardData = [
                'resumen' => $this->metricasService->obtenerResumenDashboard(),
                'tendencias_mensuales' => $this->metricasService->obtenerMetricas(
                    'volumen',
                    $inicioMes,
                    $hoy,
                    'diaria'
                ),
                'distribucion_usuarios' => $this->metricasService->obtenerDistribucionUsuarios(),
                'metricas_principales' => [
                    'volumen' => $this->metricasService->obtenerKPIsVolumen($inicioMes, $hoy),
                    'actividad' => $this->metricasService->obtenerKPIsActividad($inicioMes, $hoy),
                    'calidad' => $this->metricasService->obtenerKPIsCalidad()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en obtenerDashboard: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard'
            ], 500);
        }
    }

    public function exportarMetricas(Request $request)
    {
        try {
            // Validar entrada (mismos filtros que obtenerMetricas)
            $validated = $request->validate([
                'reporte' => 'required|string|in:volumen,crecimiento,actividad,comportamiento,calidad',
                'fecha_desde' => 'nullable|date|before_or_equal:fecha_hasta',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'tipo_usuario' => 'nullable|string|in:adoptante,veterinario,admin,ong,usuario',
                'estado' => 'nullable|string|in:activo,inactivo,bloqueado,verificado,no_verificado',
                'agrupacion' => 'nullable|string|in:diaria,semanal,mensual,trimestral,anual'
            ]);
            
            // Obtener métricas (sin paginación para exportación)
            $resultado = $this->metricasService->obtenerMetricas(
                $request->reporte,
                Carbon::parse($request->fecha_desde ?? now()->subMonth())->startOfDay(),
                Carbon::parse($request->fecha_hasta ?? now())->endOfDay(),
                $request->agrupacion ?? 'mensual',
                $request->tipo_usuario,
                $request->estado
            );
            
            // Usar el exportador
            return Excel::download(
                new UsuarioMetricasExport($resultado['metricas'] ?? [], $request->reporte),
                'metricas-usuarios-' . now()->format('Y-m-d-H-i') . '.csv'
            );
            
        } catch (\Exception $e) {
            Log::error('Error exportando métricas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar métricas'
            ], 500);
        }
    }
}