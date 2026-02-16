<?php
// app/Http/Controllers/Api/ReporteController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ReportesUsuarios;
use App\Models\EjecucionReporteUsuario;
use App\Models\User;
use App\Models\Veterinario;
use App\Models\UbicacionUsuario;
use App\Models\Usuario;
use App\Services\Reportes\ReporteUsuariosService;
use App\Services\Reportes\ReporteVeterinariosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\Reportes\PdfExportService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ReporteUsuarioController extends Controller
{
    /**
     * Obtener lista de reportes
     */
    public function index(Request $request)
    {
        $query = ReportesUsuarios::with(['usuario', 'ejecuciones' => function ($query) {
            $query->latest()->limit(5);
        }]);

        // Filtros
        if ($request->has('tipo')) {
            $query->where('tipo_reporte', $request->tipo);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('programado')) {
            $query->where('programado', $request->boolean('programado'));
        }

        // Ordenamiento
        $orden = $request->get('orden', 'desc');
        $ordenPor = $request->get('orden_por', 'created_at');
        $query->orderBy($ordenPor, $orden);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $reportes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reportes,
            'estadisticas' => [
                'total_reportes' => ReportesUsuarios::count(),
                'activos' => ReportesUsuarios::where('estado', ReportesUsuarios::ESTADO_ACTIVO)->count(),
                'programados' => ReportesUsuarios::programados()->count()
            ]
        ]);
    }

    /**
     * Crear un nuevo reporte
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_reporte' => 'required|in:' . implode(',', [
                ReportesUsuarios::TIPO_USUARIOS,
                ReportesUsuarios::TIPO_VETERINARIOS,
                ReportesUsuarios::TIPO_ADOPCIONES,
                ReportesUsuarios::TIPO_METRICAS,
                ReportesUsuarios::TIPO_PERSONALIZADO
            ]),
            'configuracion' => 'nullable|array',
            'parametros' => 'nullable|array',
            'filtros' => 'nullable|array',
            'programado' => 'boolean',
            'frecuencia' => 'nullable|required_if:programado,true|in:' . implode(',', [
                ReportesUsuarios::FRECUENCIA_DIARIA,
                ReportesUsuarios::FRECUENCIA_SEMANAL,
                ReportesUsuarios::FRECUENCIA_MENSUAL,
                ReportesUsuarios::FRECUENCIA_TRIMESTRAL,
                ReportesUsuarios::FRECUENCIA_ANUAL
            ])
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $reporte = ReportesUsuarios::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'tipo_reporte' => $request->tipo_reporte,
            'configuracion' => $request->configuracion,
            'parametros' => $request->parametros,
            'filtros' => $request->filtros,
            'programado' => $request->boolean('programado', false),
            'frecuencia' => $request->programado ? $request->frecuencia : null,
            'user_id' => auth()->id(),
            'estado' => ReportesUsuarios::ESTADO_ACTIVO
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reporte creado exitosamente',
            'data' => $reporte
        ], 201);
    }


    /**
     * Ejecutar un reporte directamente (sin guardar primero)
     */
    public function ejecutarDirecto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parametros' => 'required|array',
            'parametros.metricas' => 'required|array',
            'parametros.fecha_inicio' => 'nullable|date',
            'parametros.fecha_fin' => 'nullable|date|after_or_equal:parametros.fecha_inicio',
            'parametros.segmentacion' => 'nullable|string',
            'parametros.comparacion_periodo' => 'nullable|string',
            'parametros.filtros' => 'nullable|array',
            'formato' => 'nullable|in:json,pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar autenticación
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Crear un reporte temporal
            $reporte = new ReportesUsuarios();
            $reporte->tipo_reporte = ReportesUsuarios::TIPO_USUARIOS;
            $reporte->configuracion = $request->configuracion ?? [];
            $reporte->parametros = $request->parametros;
            $reporte->user_id = $user->id;

            // Ejecutar el servicio
            $service = new ReporteUsuariosService($reporte);
            $resultados = $service->ejecutar();

            // Guardar ejecución si se desea
            $formato = $request->get('formato', 'json');
            
            if ($formato !== 'json') {
                $ejecucion = EjecucionReporteUsuario::create([
                    'reporte_id' => null, // No asociado a un reporte guardado
                    'user_id' => $user->id,
                    'parametros' => $request->parametros,
                    'formato' => $formato,
                    'estado' => EjecucionReporteUsuario::ESTADO_COMPLETADO,
                    'tiempo_ejecucion' => 0, // Podrías medir esto
                    'resultados' => $resultados
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reporte generado exitosamente',
                'data' => [
                    'resultados' => $resultados,
                    'metadatos' => [
                        'fecha_generacion' => now()->toDateTimeString(),
                        'metricas_seleccionadas' => count($request->parametros['metricas'] ?? []),
                        'usuario' => $user->email
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error ejecutando reporte directo: ' . $e->getMessage(), [
                'parametros' => $request->parametros,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el reporte',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener métricas disponibles para usuarios
     */
    public function metricasUsuarios()
    {
        $metricas = [
            [
                'id' => 'total_usuarios',
                'name' => 'Total de Usuarios',
                'description' => 'Número total de usuarios registrados',
                'icon' => '👥',
                'tipo' => 'contador',
                'categoria' => 'general'
            ],
            [
                'id' => 'usuarios_activos',
                'name' => 'Usuarios Activos',
                'description' => 'Usuarios con estado activo',
                'icon' => '✅',
                'tipo' => 'contador',
                'categoria' => 'general'
            ],
            [
                'id' => 'nuevos_usuarios',
                'name' => 'Nuevos Usuarios',
                'description' => 'Usuarios registrados en el período',
                'icon' => '🆕',
                'tipo' => 'contador',
                'categoria' => 'crecimiento'
            ],
            [
                'id' => 'tipo_usuario',
                'name' => 'Distribución por Tipo',
                'description' => 'Distribución de usuarios por tipo (normal, veterinario, admin)',
                'icon' => '📊',
                'tipo' => 'distribucion',
                'categoria' => 'segmentacion'
            ],
            [
                'id' => 'ubicaciones',
                'name' => 'Ubicaciones de Usuarios',
                'description' => 'Distribución geográfica de usuarios',
                'icon' => '📍',
                'tipo' => 'geografico',
                'categoria' => 'segmentacion'
            ],
            [
                'id' => 'registro_por_fuente',
                'name' => 'Registro por Fuente',
                'description' => 'Distribución por método de registro (Google, Facebook, Email)',
                'icon' => '🔗',
                'tipo' => 'distribucion',
                'categoria' => 'registro'
            ],
            [
                'id' => 'edad_promedio',
                'name' => 'Edad Promedio',
                'description' => 'Edad promedio de los usuarios',
                'icon' => '🎂',
                'tipo' => 'estadistico',
                'categoria' => 'demograficos'
            ],
            [
                'id' => 'usuarios_sancionados',
                'name' => 'Usuarios Sancionados',
                'description' => 'Usuarios con sanciones activas',
                'icon' => '⚠️',
                'tipo' => 'contador',
                'categoria' => 'moderacion'
            ],
            [
                'id' => 'retencion',
                'name' => 'Tasa de Retención',
                'description' => 'Porcentaje de usuarios activos en los últimos 30 días',
                'icon' => '📈',
                'tipo' => 'porcentaje',
                'categoria' => 'retencion'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $metricas,
            'segmentaciones' => [
                ['value' => 'tipo_usuario', 'label' => 'Tipo de Usuario'],
                ['value' => 'fuente_registro', 'label' => 'Fuente de Registro'],
                ['value' => 'ubicacion', 'label' => 'Ubicación'],
                ['value' => 'estado', 'label' => 'Estado del Usuario']
            ],
            'filtros' => [
                ['id' => 'estado_usuario', 'label' => 'Estado', 'type' => 'select', 'options' => ['activo', 'inactivo', 'pendiente']],
                ['id' => 'tipo_usuario', 'label' => 'Tipo', 'type' => 'select', 'options' => ['Usuario', 'Veterinario', 'Administrador']],
                ['id' => 'fuente_registro', 'label' => 'Fuente', 'type' => 'select', 'options' => ['google', 'facebook', 'email']]
            ]
        ]);
    }

    /**
     * Ejecutar un reporte (con o sin ID)
     */
    public function ejecutar(Request $request, $id = null)
    {
        // Validación común
        $validator = Validator::make($request->all(), [
            'parametros' => 'required|array',
            'parametros.metricas' => 'required|array',
            'parametros.fecha_inicio' => 'nullable|date',
            'parametros.fecha_fin' => 'nullable|date|after_or_equal:parametros.fecha_inicio',
            'parametros.segmentacion' => 'nullable|string',
            'parametros.comparacion_periodo' => 'nullable|string',
            'parametros.filtros' => 'nullable|array',
            'formato' => 'nullable|in:json,pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar autenticación
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Si hay ID, es un reporte guardado
            if ($id) {
                $reporte = ReportesUsuarios::findOrFail($id);
                
                // Ejecutar el servicio
                $service = $this->obtenerServicioReporte($reporte);
                
                // Actualizar parámetros si se proporcionan nuevos
                if ($request->has('parametros')) {
                    $service->setParametros($request->parametros);
                }
                
                $resultados = $service->ejecutar();
                
                // Guardar ejecución
                $formato = $request->get('formato', 'json');
                $ejecucion = EjecucionReporteUsuario::create([
                    'reporte_id' => $reporte->id,
                    'user_id' => $user->id,
                    'parametros' => $request->parametros ?? $reporte->parametros,
                    'formato' => $formato,
                    'estado' => EjecucionReporteUsuario::ESTADO_COMPLETADO,
                    'tiempo_ejecucion' => 0,
                    'resultados' => $resultados
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reporte generado exitosamente',
                    'data' => [
                        'reporte' => $reporte,
                        'ejecucion' => $ejecucion,
                        'resultados' => $resultados
                    ]
                ]);
            } 
            // Si NO hay ID, es una ejecución directa
            else {
                // Crear un reporte temporal
                $reporte = new ReportesUsuarios();
                $reporte->tipo_reporte = ReportesUsuarios::TIPO_USUARIOS;
                $reporte->configuracion = $request->configuracion ?? [];
                $reporte->parametros = $request->parametros;
                $reporte->user_id = $user->id;

                // Ejecutar el servicio
                $service = new ReporteUsuariosService($reporte);
                $resultados = $service->ejecutar();

                // Guardar ejecución si se desea
                $formato = $request->get('formato', 'json');
                
                if ($formato !== 'json') {
                    $ejecucion = EjecucionReporteUsuario::create([
                        'reporte_id' => null, // No asociado a un reporte guardado
                        'user_id' => $user->id,
                        'parametros' => $request->parametros,
                        'formato' => $formato,
                        'estado' => EjecucionReporteUsuario::ESTADO_EXITO,
                        'tiempo_ejecucion' => 0,
                        'resultados' => $resultados
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Reporte generado exitosamente',
                    'data' => [
                        'resultados' => $resultados,
                        'metadatos' => [
                            'fecha_generacion' => now()->toDateTimeString(),
                            'metricas_seleccionadas' => count($request->parametros['metricas'] ?? []),
                            'usuario' => $user->email
                        ]
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error ejecutando reporte: ' . $e->getMessage(), [
                'id' => $id,
                'parametros' => $request->parametros,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el reporte',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Helper: Obtener servicio de reporte según tipo
     */
    private function obtenerServicioReporte(ReportesUsuarios $reporte)
    {
        switch ($reporte->tipo_reporte) {
            case ReportesUsuarios::TIPO_USUARIOS:
                return new ReporteUsuariosService($reporte);
            case ReportesUsuarios::TIPO_VETERINARIOS:
                // return new ReporteVeterinariosService($reporte);
            // Agregar más servicios según sea necesario
            default:
                throw new \Exception("Tipo de reporte no soportado: {$reporte->tipo_reporte}");
        }
    }

    /**
     * Obtener ejecuciones de un reporte
     */
    public function ejecuciones($id)
    {
        $reporte = ReportesUsuarios::findOrFail($id);
        
        $ejecuciones = $reporte->ejecuciones()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $estadisticas = [
            'total_ejecuciones' => $reporte->ejecuciones()->count(),
            'exitosas' => $reporte->ejecuciones()->exitosas()->count(),
            'fallidas' => $reporte->ejecuciones()->fallidas()->count(),
            'tiempo_promedio' => $reporte->ejecuciones()->exitosas()->avg('tiempo_ejecucion'),
            'ultima_ejecucion' => $reporte->ejecuciones()->latest()->first()
        ];

        return response()->json([
            'success' => true,
            'data' => $ejecuciones,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Exportar reporte a diferentes formatos
     */
    // En el método exportar
    public function exportar(Request $request, $id)
    {
        Log::info('Datos recibidos para exportación:', $request->all());
        Log::info('Estructura de datos:', ['datos' => $request->datos ? 'presente' : 'ausente']); // ← CORREGIDO
        $ejecucion = EjecucionReporteUsuario::findOrFail($id);
        
        $formato = $request->get('formato', $ejecucion->formato);
        
        if ($formato === 'pdf') {
            // Usar el servicio de PDF
            $pdfService = new PdfExportService();
            
            // Generar contenido PDF
            $pdfContent = $pdfService->generarPdf(
                $ejecucion->resultados,
                $ejecucion->reporte->nombre ?? 'Reporte'
            );
            
            // Generar nombre de archivo
            $nombreArchivo = 'reporte-' . $ejecucion->id . '-' . now()->format('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        }
        
        // Para otros formatos (Excel, CSV)
        return $this->exportarOtrosFormatos($ejecucion, $formato);
    }

    /**
     * Eliminar un reporte
     */
    public function destroy($id)
    {
        try {
            $reporte = ReportesUsuarios::findOrFail($id);
            
            // Verificar permisos (opcional, pero recomendado)
            if (auth()->id() !== $reporte->user_id && !auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este reporte'
                ], 403);
            }
            
            // Eliminar ejecuciones asociadas primero (si existe relación con cascade)
            $reporte->ejecuciones()->delete();
            
            // Eliminar el reporte
            $reporte->delete();
            
            Log::info("Reporte eliminado: {$id} por usuario: " . auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error eliminando reporte: ' . $e->getMessage(), [
                'reporte_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el reporte',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // Nuevo método para exportación directa
    public function exportarDirecto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'datos' => 'required|array',
            'formato' => 'required|in:pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->formato === 'pdf') {
                $pdfService = new PdfExportService();
                $pdfContent = $pdfService->generarPdf(
                    $request->datos,
                    $request->titulo
                );

                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $request->titulo . '.pdf"');
            }

            // ... otros formatos

        } catch (\Exception $e) {
            Log::error('Error exportando reporte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas generales del sistema
     */
    public function estadisticasGenerales()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'usuarios' => [
                    'total' => User::count(),
                    'activos' => User::whereHas('userable', function ($q) {
                        $q->where('activo', true);
                    })->count(),
                    'nuevos_hoy' => User::whereDate('created_at', today())->count(),
                    'tipo_distribucion' => [
                        'usuarios' => User::where('userable_type', 'App\Models\Usuario')->count(),
                        'veterinarios' => User::where('userable_type', 'App\Models\Veterinario')->count(),
                        'administradores' => User::where('userable_type', 'App\Models\Administrador')->count()
                    ]
                ],
                'veterinarios' => [
                    'total' => Veterinario::count(),
                    'aprobados' => Veterinario::aprobados()->count(),
                    'pendientes' => Veterinario::pendientes()->count()
                ],
                'ubicaciones' => [
                    'usuarios_con_ubicacion' => UbicacionUsuario::distinct('user_id')->count(),
                    'ciudades_unicas' => UbicacionUsuario::distinct('city')->count(),
                    'paises_unicos' => UbicacionUsuario::distinct('country')->count()
                ],
                'registros' => [
                    'por_google' => User::whereNotNull('google_id')->count(),
                    'por_facebook' => User::whereNotNull('facebook_id')->count(),
                    'por_email' => User::whereNull('google_id')->whereNull('facebook_id')->count()
                ]
            ]
        ]);
    }

    // En ReporteUsuarioController.php - Opción 2
    /**
     * Exportar reporte guardado
     */
    public function exportarReporte($id, Request $request)
    {
        Log::info("📄 Exportando reporte ID: {$id}");
        
        try {
            // Buscar el reporte
            $reporte = ReportesUsuarios::with(['usuario', 'ejecuciones' => function($query) {
                $query->latest()->first();
            }])->findOrFail($id);
            
            Log::info("Reporte encontrado: {$reporte->nombre}");
            
            // Si tiene ejecuciones, usar la última
            if ($reporte->ejecuciones->count() > 0) {
                $ejecucion = $reporte->ejecuciones->first();
                return $this->exportar($request, $ejecucion->id);
            }
            
            // Si no tiene ejecuciones, ejecutarlo primero
            Log::info("El reporte no tiene ejecuciones, ejecutando...");
            
            // Crear parámetros por defecto
            $parametros = $reporte->parametros ?? [];
            if (empty($parametros)) {
                $parametros = [
                    'metricas' => ['total_usuarios'],
                    'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
                    'fecha_fin' => now()->format('Y-m-d'),
                    'segmentacion' => null,
                    'comparacion_periodo' => null
                ];
            }
            
            // Crear request de ejecución
            $ejecucionRequest = new Request([
                'parametros' => $parametros,
                'formato' => 'json'
            ]);
            
            // Ejecutar el reporte
            $response = $this->ejecutar($ejecucionRequest, $id);
            
            if (!$response->getData()->success) {
                throw new \Exception('No se pudo ejecutar el reporte');
            }
            
            // Obtener la ejecución recién creada
            $ejecucion = $reporte->ejecuciones()->latest()->first();
            
            if (!$ejecucion) {
                throw new \Exception('No se pudo obtener la ejecución');
            }
            
            // Exportar la ejecución
            return $this->exportar($request, $ejecucion->id);
            
        } catch (\Exception $e) {
            Log::error("Error exportando reporte {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar ejecución específica (método auxiliar)
     */
    public function exportarEjecucion($ejecucionId, Request $request)
    {
        return $this->exportar($request, $ejecucionId);
    }


    /**
     * Helper: Generar archivo de exportación
     */
    private function generarArchivoExportacion(EjecucionReporteUsuario $ejecucion, string $formato): ?string
    {
        try {
            $resultados = $ejecucion->resultados;
            $nombreArchivo = "reporte-{$ejecucion->reporte->id}-{$ejecucion->id}-" . now()->format('Y-m-d-H-i-s');
            
            switch ($formato) {
                case EjecucionReporteUsuario::FORMATO_PDF:
                    // Usar DomPDF o similar
                    $ruta = "reportes/pdf/{$nombreArchivo}.pdf";
                    // Implementar generación de PDF
                    break;
                    
                case EjecucionReporteUsuario::FORMATO_EXCEL:
                    $ruta = "reportes/excel/{$nombreArchivo}.xlsx";
                    // Implementar generación de Excel
                    break;
                    
                case EjecucionReporteUsuario::FORMATO_CSV:
                    $ruta = "reportes/csv/{$nombreArchivo}.csv";
                    $csv = $this->convertirACsv($resultados);
                    Storage::put($ruta, $csv);
                    break;
                    
                default:
                    return null;
            }
            
            $ejecucion->update(['ruta_archivo' => $ruta]);
            return $ruta;
            
        } catch (\Exception $e) {
            Log::error('Error generando archivo de exportación:', [
                'ejecucion_id' => $ejecucion->id,
                'formato' => $formato,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Helper: Convertir resultados a CSV
     */
    private function convertirACsv(array $data): string
    {
        $csv = [];
        
        // Encabezados
        $csv[] = ['Métrica', 'Valor', 'Detalles'];
        
        // Datos
        foreach ($data['metricas'] ?? [] as $key => $metrica) {
            $csv[] = [
                $metrica['etiqueta'] ?? $key,
                $metrica['valor'] ?? '',
                is_array($metrica['detalle'] ?? null) 
                    ? json_encode($metrica['detalle'])
                    : ($metrica['detalle'] ?? '')
            ];
        }
          // Convertir a string CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);
        
        return $csvString;
    }

    // En ReporteUsuarioController.php, agrega este método
    public function exportarConGraficos(Request $request)
    {
        Log::info('📤 Exportar con gráficos - Datos recibidos:', [
            'request_all' => $request->all(),
            'has_datos' => $request->has('datos'),
            'has_grafico' => $request->has('grafico'),
            'content_type' => $request->header('Content-Type')
        ]);

        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'datos' => 'required|array',
            'grafico' => 'required|array', // Cambiado de nullable a required
            'grafico.type' => 'required|string',
            'grafico.data' => 'required|array',
            'grafico.options' => 'nullable|array',
            'formato' => 'required|in:pdf'
        ]);

        if ($validator->fails()) {
            Log::error('❌ Validación fallida:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pdfService = new PdfExportService();
            
            // Debug: Ver qué llega
            Log::info('📊 Datos para PDF:', [
                'titulo' => $request->titulo,
                'datos_tipo' => gettype($request->datos),
                'grafico_tipo' => gettype($request->grafico),
                'grafico_contenido' => json_encode($request->grafico)
            ]);
            
            $pdfContent = $pdfService->generarPdfConGraficos(
                $request->datos,
                $request->titulo,
                $request->grafico
            );

            $nombreArchivo = str_replace([' ', '/', '\\'], '_', $request->titulo) . '.pdf';
            
            Log::info('✅ PDF generado exitosamente:', [
                'tamaño' => strlen($pdfContent),
                'nombre' => $nombreArchivo
            ]);
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                
        } catch (\Exception $e) {
            Log::error('❌ Error exportando reporte con gráficos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}   
   