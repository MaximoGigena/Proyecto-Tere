<?php
// app/Http/Controllers/ControllersProcedimientos/CirugiaController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Cirugia;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoCirugia;
use App\Models\CentroVeterinario;
use App\Models\TiposProcedimientos\TipoDiagnostico; 
use App\Models\FarmacoAsociado;
use App\Models\ProcedimientoDiagnostico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\EnvioDocumentosService;

class CirugiaController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nueva cirugía
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposCirugia = TipoCirugia::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('cirugias.create', compact('mascota', 'tiposCirugia', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nueva cirugía
     */
    public function store(Request $request, $mascotaId): JsonResponse
    {
        // Validación según los campos del formulario Vue
        $validated = $request->validate([
            // Campos obligatorios
            'tipo_cirugia_id' => 'required|exists:tipos_cirugia,id',
            'fecha' => 'required|date',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'resultado' => 'required|in:satisfactorio,complicaciones,estable,critico',
            'estado' => 'required|in:recuperacion,alta,seguimiento,hospitalizado',
            
            // Campos opcionales
            'fecha_control_estimada' => 'nullable|date',
            'descripcion_procedimiento' => 'nullable|string|max:1000',
            'medicacion_postquirurgica' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string|max:500',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
            
            // Diagnósticos asociados
            'diagnosticos' => 'nullable|array',
            'diagnosticos.*' => 'exists:tipos_diagnostico,id',
            
            // Fármacos asociados
            'farmacos_asociados' => 'nullable|array',
            'farmacos_asociados.*.farmaco_id' => 'required|exists:tipos_farmaco,id',
            'farmacos_asociados.*.dosis' => 'required|numeric|min:0.001',
            'farmacos_asociados.*.frecuencia' => 'nullable|string',
            'farmacos_asociados.*.duracion' => 'nullable|string',
            'farmacos_asociados.*.observaciones' => 'nullable|string|max:1000',
            'farmacos_asociados.*.etapa_aplicacion' => 'required|in:prequirurgica,transquirurgica,postquirurgica_inmediata,postquirurgica_tardia',
            'farmacos_asociados.*.es_dosis_unica' => 'boolean',
            
            // Archivos adjuntos
            'archivos.*' => 'nullable|file|max:10240', // 10MB máximo
        ]);

        try {
            $cirugiaCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$cirugiaCreada, &$mascotaData, $request) {
                // 1. Crear el registro específico de Cirugia
                $cirugia = Cirugia::create([
                    'tipo_cirugia_id' => $validated['tipo_cirugia_id'],
                    'fecha_cirugia' => $validated['fecha'],
                    'resultado' => $validated['resultado'],
                    'estado_actual' => $validated['estado'],
                    'diagnostico_causa' => $this->procesarDiagnosticosCausa($validated['diagnosticos'] ?? []),
                    'fecha_control_estimada' => $validated['fecha_control_estimada'] ?? null,
                    'descripcion_procedimiento' => $validated['descripcion_procedimiento'] ?? null,
                    'medicacion_postquirurgica' => $validated['medicacion_postquirurgica'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'clinico',
                    'fecha_aplicacion' => $validated['fecha'],
                    'observaciones' => $validated['descripcion_procedimiento'] ?? null,
                    'costo' => null, // Puedes agregar campo de costo si lo necesitas
                ]);

                // 3. Asociar la cirugía con el proceso médico
                $cirugia->procesoMedico()->save($procesoMedico);

                // 4. Guardar fármacos asociados (si existen)
                if (isset($validated['farmacos_asociados']) && is_array($validated['farmacos_asociados'])) {
                    $this->guardarFarmacosAsociados($cirugia, $validated['farmacos_asociados']);
                }

                // 5. Guardar diagnósticos asociados
                if (isset($validated['diagnosticos']) && is_array($validated['diagnosticos'])) {
                    $this->guardarDiagnosticosAsociados($cirugia, $validated['diagnosticos']);
                }

                // 6. Manejar archivos adjuntos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($cirugia, $request->file('archivos'));
                }

                // 7. Cargar relaciones para la respuesta
                $cirugiaCreada = $cirugia->load([
                    'tipoCirugia',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario',
                    'farmacosAsociados.tipoFarmaco'
                ]);
                
                // 8. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 9. Enviar certificado PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($cirugiaCreada && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoCirugia(
                        $cirugiaCreada, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y certificado enviado';
                    
                    Log::info('✅ Certificado de cirugía enviado exitosamente', [
                        'cirugia_id' => $cirugiaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando certificado de cirugía', [
                        'cirugia_id' => $cirugiaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('✅ Cirugía registrada exitosamente', [
                'cirugia_id' => $cirugiaCreada->id,
                'mascota_id' => $mascotaId,
                'usuario_id' => Auth::id(),
                'farmacos_asociados' => count($validated['farmacos_asociados'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cirugía registrada exitosamente' . $mensajeEnvio,
                'data' => [
                    'cirugia' => $cirugiaCreada,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar cirugía', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las cirugías de una mascota
     */
    public function index($mascotaId): JsonResponse
    {
        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener las cirugías con sus relaciones
            $cirugias = Cirugia::with([
                'tipoCirugia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'farmacosAsociados.tipoFarmaco',
                'diagnosticosAsociados' // Solo cargar la relación directa
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha_cirugia', 'desc')
            ->get()
            ->map(function($cirugia) {
                // Procesar diagnósticos
                $diagnosticosArray = [];
                if ($cirugia->diagnosticosAsociados && $cirugia->diagnosticosAsociados->count() > 0) {
                    $diagnosticosArray = $cirugia->diagnosticosAsociados->map(function($diagnosticoAsociado) {
                        return [
                            'id' => $diagnosticoAsociado->diagnostico_id,
                            'nombre' => $diagnosticoAsociado->nombre_diagnostico ?? 'Diagnóstico no especificado'
                        ];
                    })->toArray();
                }
                
                // Formatear datos para la vista Vue
                return [
                    'id' => $cirugia->id,
                    'tipo_cirugia' => $cirugia->tipoCirugia->nombre ?? 'Tipo no especificado',
                    'fecha' => $cirugia->fecha_cirugia ? $cirugia->fecha_cirugia->format('Y-m-d H:i:s') : null,
                    'resultado' => $cirugia->resultado,
                    'estado' => $cirugia->estado_actual,
                    'centro_veterinario' => $cirugia->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'fecha_control' => $cirugia->fecha_control_estimada ? $cirugia->fecha_control_estimada->format('Y-m-d') : null,
                    'diagnosticos' => $diagnosticosArray, // Enviar en el formato que espera la vista Vue
                    'descripcion_procedimiento' => $cirugia->descripcion_procedimiento,
                    'medicacion_postquirurgica' => $cirugia->medicacion_postquirurgica,
                    'recomendaciones_tutor' => $cirugia->recomendaciones_tutor,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $cirugias
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener cirugías: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las cirugías: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString() // Para debug
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una cirugía específica
     */
    public function show($mascotaId, $cirugiaId): JsonResponse
    {
        try {
            $cirugia = Cirugia::with([
                'tipoCirugia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procesoMedico.mascota',
                'farmacosAsociados.tipoFarmaco',
                'diagnosticosAsociados' // Solo cargar la relación directa
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->findOrFail($cirugiaId);

            // Procesar diagnósticos para la respuesta
            $diagnosticosArray = [];
            if ($cirugia->diagnosticosAsociados && $cirugia->diagnosticosAsociados->count() > 0) {
                $diagnosticosArray = $cirugia->diagnosticosAsociados->map(function($diagnosticoAsociado) {
                    return [
                        'id' => $diagnosticoAsociado->diagnostico_id,
                        'nombre' => $diagnosticoAsociado->nombre_diagnostico ?? 'Diagnóstico no especificado'
                    ];
                })->toArray();
            }

            // Cargar archivos adjuntos si existen
            $archivos = [];
            $archivosPath = "cirugias/{$cirugia->id}";
            if (Storage::exists($archivosPath)) {
                $archivos = Storage::files($archivosPath);
            }

            $cirugiaArray = $cirugia->toArray();
            $cirugiaArray['fecha_cirugia_formateada'] = $cirugia->fecha_cirugia->format('d/m/Y H:i');
            $cirugiaArray['resultado_display'] = $this->getResultadoDisplay($cirugia->resultado);
            $cirugiaArray['estado_display'] = $this->getEstadoDisplay($cirugia->estado_actual);
            $cirugiaArray['fecha_control_formateada'] = $cirugia->fecha_control_estimada ? $cirugia->fecha_control_estimada->format('d/m/Y') : null;
            $cirugiaArray['archivos'] = $archivos;
            
            // Agregar diagnósticos procesados
            $cirugiaArray['diagnosticos'] = $diagnosticosArray;
            
            $cirugiaArray['farmacos_asociados'] = $cirugia->farmacosAsociados->map(function($farmaco) {
                return array_merge($farmaco->toArray(), [
                    'farmaco_nombre_comercial' => $farmaco->tipoFarmaco->nombre_comercial ?? null,
                    'farmaco_nombre_generico' => $farmaco->tipoFarmaco->nombre_generico ?? null,
                    'etapa_aplicacion_texto' => $farmaco->etapa_aplicacion_texto,
                    'etapa_aplicacion_abreviada' => $farmaco->etapa_aplicacion_abreviada,
                    'dosis_formateada' => $farmaco->dosis_formateada,
                    'frecuencia_completa' => $farmaco->frecuencia_completa,
                    'duracion_completa' => $farmaco->duracion_completa
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $cirugiaArray
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener cirugía:', [
                'cirugia_id' => $cirugiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cirugía: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar cirugía
     */
    public function update(Request $request, $mascotaId, $cirugiaId): JsonResponse
    {
        try {
            $cirugia = Cirugia::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($cirugiaId);

            // CORRECCIÓN: Cambiar los nombres de validación para que coincidan con la vista Vue
            $validated = $request->validate([
                // Campos actualizables
                'tipo_cirugia_id' => 'sometimes|exists:tipos_cirugia,id',
                'fecha' => 'sometimes|date',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'resultado' => 'sometimes|in:satisfactorio,complicaciones,estable,critico',
                'estado' => 'sometimes|in:recuperacion,alta,seguimiento,hospitalizado',
                
                // ✅ CORRECCIÓN: Cambiar nombres para coincidir con la vista Vue
                'fecha_control' => 'nullable|date', // En la vista es fecha_control
                'descripcion' => 'nullable|string|max:1000', // En la vista es descripcion
                'medicacion_postquirurgica' => 'nullable|string',
                'recomendaciones' => 'nullable|string|max:500', // En la vista es recomendaciones
                
                // Fármacos asociados
                'farmacos_asociados' => 'nullable|array',
                'farmacos_asociados.*.farmaco_id' => 'required_with:farmacos_asociados|exists:tipos_farmaco,id',
                'farmacos_asociados.*.dosis' => 'required_with:farmacos_asociados|numeric|min:0.001',
                'farmacos_asociados.*.frecuencia' => 'nullable|string',
                'farmacos_asociados.*.duracion' => 'nullable|string',
                'farmacos_asociados.*.observaciones' => 'nullable|string|max:1000',
                'farmacos_asociados.*.etapa_aplicacion' => 'required_with:farmacos_asociados|in:prequirurgica,transquirurgica,postquirurgica_inmediata,postquirurgica_tardia',
                'farmacos_asociados.*.es_dosis_unica' => 'boolean',
                
                // Archivos
                'archivos.*' => 'nullable|file|max:10240',
                'archivos_a_eliminar' => 'nullable|array',
                'archivos_a_eliminar.*' => 'string',
                
                // Diagnósticos
                'diagnosticos' => 'nullable|array',
                'diagnosticos.*' => 'exists:tipos_diagnostico,id',
            ]);

            DB::transaction(function () use ($validated, $cirugia, $request) {
                // 1. Actualizar la cirugía
                $cirugia->update([
                    'tipo_cirugia_id' => $validated['tipo_cirugia_id'] ?? $cirugia->tipo_cirugia_id,
                    'fecha_cirugia' => $validated['fecha'] ?? $cirugia->fecha_cirugia,
                    'resultado' => $validated['resultado'] ?? $cirugia->resultado,
                    'estado_actual' => $validated['estado'] ?? $cirugia->estado_actual,
                    
                    // ✅ CORRECCIÓN: Usar los nombres correctos
                    'fecha_control_estimada' => $validated['fecha_control'] ?? $cirugia->fecha_control_estimada,
                    'descripcion_procedimiento' => $validated['descripcion'] ?? $cirugia->descripcion_procedimiento,
                    'medicacion_postquirurgica' => $validated['medicacion_postquirurgica'] ?? $cirugia->medicacion_postquirurgica,
                    'recomendaciones_tutor' => $validated['recomendaciones'] ?? $cirugia->recomendaciones_tutor,
                ]);

                // 2. Actualizar el proceso médico asociado
                if ($cirugia->procesoMedico) {
                    $cirugia->procesoMedico->update([
                        'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? $cirugia->procesoMedico->centro_veterinario_id,
                        'fecha_aplicacion' => $validated['fecha'] ?? $cirugia->fecha_cirugia,
                        'observaciones' => $validated['descripcion'] ?? $cirugia->descripcion_procedimiento, // ✅ Usar descripcion
                    ]);
                }

                // 3. Actualizar fármacos asociados
                if (isset($validated['farmacos_asociados'])) {
                    // Eliminar fármacos existentes
                    $cirugia->farmacosAsociados()->delete();
                    
                    // Crear nuevos fármacos
                    $this->guardarFarmacosAsociados($cirugia, $validated['farmacos_asociados']);
                }

                // 4. Actualizar diagnósticos asociados
                if (isset($validated['diagnosticos'])) {
                    // Eliminar diagnósticos existentes
                    ProcedimientoDiagnostico::where('procedimiento_id', $cirugia->id)
                        ->where('procedimiento_type', 'App\Models\ProcedimientosMedicos\Cirugia')
                        ->delete();
                    
                    // Crear nuevos diagnósticos
                    $this->guardarDiagnosticosAsociados($cirugia, $validated['diagnosticos']);
                }

                // 5. Manejar eliminación de archivos
                if (isset($validated['archivos_a_eliminar'])) {
                    $this->eliminarArchivos($cirugia, $validated['archivos_a_eliminar']);
                }

                // 6. Manejar nuevos archivos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($cirugia, $request->file('archivos'));
                }
            });

            // Recargar relaciones
            $cirugia->load([
                'tipoCirugia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'farmacosAsociados.tipoFarmaco',
                'diagnosticosAsociados'
            ]);

            Log::info('✅ Cirugía actualizada exitosamente', [
                'cirugia_id' => $cirugia->id,
                'mascota_id' => $mascotaId,
                'campos_opcionales' => [
                    'fecha_control' => $validated['fecha_control'] ?? null,
                    'descripcion' => $validated['descripcion'] ?? null,
                    'recomendaciones' => $validated['recomendaciones'] ?? null,
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cirugía actualizada exitosamente',
                'data' => $cirugia
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar cirugía', [
                'cirugia_id' => $cirugiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all() // Agregar para debug
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cirugía
     */
    public function destroy($mascotaId, $cirugiaId): JsonResponse
    {
        try {
            $cirugia = Cirugia::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($cirugiaId);

            // Verificar que no esté ya eliminada
            if ($cirugia->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cirugía ya ha sido eliminada'
                ], 400);
            }

            DB::transaction(function () use ($cirugia) {
                // 1. Marcar la cirugía como eliminada (baja lógica)
                $cirugia->delete(); // Soft delete

                // 2. Opcional: Marcar también el proceso médico como eliminado si también usa soft deletes
                if ($cirugia->procesoMedico && method_exists($cirugia->procesoMedico, 'delete')) {
                    $cirugia->procesoMedico->delete();
                }

                // 3. Guardar información de quién eliminó (opcional)
                $cirugia->update([
                    'eliminado_por' => Auth::id(),
                    'motivo_eliminacion' => request()->input('motivo', 'Eliminación solicitada por usuario')
                ]);
            });

            Log::info('✅ Cirugía marcada como eliminada (baja lógica)', [
                'cirugia_id' => $cirugiaId,
                'mascota_id' => $mascotaId,
                'eliminado_por' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cirugía eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar cirugía', [
                'cirugia_id' => $cirugiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cirugía: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtener estadísticas de cirugías de una mascota
     */
    public function estadisticas($mascotaId): JsonResponse
    {
        try {
            $estadisticas = Cirugia::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->selectRaw('resultado, COUNT(*) as cantidad')
            ->groupBy('resultado')
            ->get()
            ->pluck('cantidad', 'resultado');

            $total = $estadisticas->sum();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'por_resultado' => $estadisticas,
                    'resultados_display' => Cirugia::getResultadosPermitidos(),
                    'estados_display' => Cirugia::getEstadosPermitidos()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de cirugías: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo($cirugiaId, $nombreArchivo)
    {
        try {
            $cirugia = Cirugia::findOrFail($cirugiaId);
            $path = "cirugias/{$cirugia->id}/{$nombreArchivo}";

            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return Storage::download($path);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo de cirugía', [
                'cirugia_id' => $cirugiaId,
                'archivo' => $nombreArchivo,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar diagnósticos asociados a una cirugía
     */
    private function guardarDiagnosticosAsociados(Cirugia $cirugia, array $diagnosticosIds)
    {
        foreach ($diagnosticosIds as $diagnosticoId) {
            // Determinar el tipo de diagnóstico (de catálogo)
            $diagnosticoType = 'App\Models\TiposProcedimientos\TipoDiagnostico'; // CORREGIDO
            
            // Cargar el diagnóstico para tomar snapshot
            $diagnostico = TipoDiagnostico::find($diagnosticoId);
            
            if ($diagnostico) {
                ProcedimientoDiagnostico::create([
                    'procedimiento_id' => $cirugia->id,
                    'procedimiento_type' => 'App\Models\ProcedimientosMedicos\Cirugia',
                    'diagnostico_id' => $diagnosticoId,
                    'diagnostico_type' => $diagnosticoType,
                    'estado' => 'activo',
                    'relevancia' => 'primario',
                    'observaciones' => null,
                    'nombre_diagnostico' => $diagnostico->nombre,
                    'evolucion' => $diagnostico->evolucion ?? null,
                    'clasificacion' => $diagnostico->clasificacion ?? null,
                    'sintomas_caracteristicos' => $diagnostico->sintomas_caracteristicos ?? null,
                    'veterinario_id' => Auth::id(),
                    'fecha_asociacion' => now(),
                ]);
                
                Log::info('✅ Diagnóstico asociado a cirugía', [
                    'cirugia_id' => $cirugia->id,
                    'diagnostico_id' => $diagnosticoId,
                    'diagnostico_nombre' => $diagnostico->nombre,
                    'veterinario_id' => Auth::id()
                ]);
            }
        }
    }

    /**
     * Métodos auxiliares privados
     */
    
    private function guardarArchivos($cirugia, $archivos)
    {
        $path = "cirugias/{$cirugia->id}";
        
        foreach ($archivos as $archivo) {
            if ($archivo && $archivo->isValid()) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $extension = $archivo->getClientOriginalExtension();
                $nombreUnico = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
                
                Storage::putFileAs($path, $archivo, $nombreUnico);
                
                Log::info('✅ Archivo guardado para cirugía', [
                    'cirugia_id' => $cirugia->id,
                    'archivo' => $nombreUnico,
                    'original' => $nombreOriginal
                ]);
            }
        }
    }

    private function eliminarArchivos($cirugia, $nombresArchivos)
    {
        foreach ($nombresArchivos as $nombreArchivo) {
            $path = "cirugias/{$cirugia->id}/{$nombreArchivo}";
            if (Storage::exists($path)) {
                Storage::delete($path);
                
                Log::info('✅ Archivo eliminado de cirugía', [
                    'cirugia_id' => $cirugia->id,
                    'archivo' => $nombreArchivo
                ]);
            }
        }
    }

    private function procesarDiagnosticosCausa($diagnosticosIds)
    {
        if (empty($diagnosticosIds)) {
            return 'Sin diagnóstico específico';
        }

        // Aquí podrías cargar los nombres de los diagnósticos
        // Por ahora, simplemente devolvemos un texto con los IDs
        return 'Diagnósticos asociados: ' . implode(', ', $diagnosticosIds);
    }

    private function guardarFarmacosAsociados($cirugia, $farmacosData)
    {
        foreach ($farmacosData as $farmacoData) {
            // Procesar la frecuencia (ej: "7 d" -> frecuencia_valor: 7, frecuencia_unidad: d)
            $frecuenciaPartes = explode(' ', $farmacoData['frecuencia'] ?? '');
            $frecuenciaValor = count($frecuenciaPartes) > 0 ? intval($frecuenciaPartes[0]) : null;
            $frecuenciaUnidad = count($frecuenciaPartes) > 1 ? $frecuenciaPartes[1] : null;

            // Procesar la duración (ej: "7 d" -> duracion_valor: 7, duracion_unidad: d)
            $duracionPartes = explode(' ', $farmacoData['duracion'] ?? '');
            $duracionValor = count($duracionPartes) > 0 ? intval($duracionPartes[0]) : null;
            $duracionUnidad = count($duracionPartes) > 1 ? $duracionPartes[1] : null;

            // Procesar la dosis (ej: "5 mg" -> dosis_prescrita: 5, unidad_dosis: mg)
            $dosisPartes = explode(' ', $farmacoData['dosis'] ?? '');
            $dosisPrescrita = count($dosisPartes) > 0 ? floatval($dosisPartes[0]) : 0;
            $unidadDosis = count($dosisPartes) > 1 ? $dosisPartes[1] : 'mg';

            $cirugia->farmacosAsociados()->create([
                'tipo_farmaco_id' => $farmacoData['farmaco_id'],
                'dosis_prescrita' => $dosisPrescrita,
                'unidad_dosis' => $unidadDosis,
                'es_dosis_unica' => $farmacoData['es_dosis_unica'] ?? false,
                'frecuencia_valor' => $frecuenciaValor,
                'frecuencia_unidad' => $frecuenciaUnidad,
                'duracion_valor' => $duracionValor,
                'duracion_unidad' => $duracionUnidad,
                'etapa_aplicacion' => $farmacoData['etapa_aplicacion'],
                'observaciones' => $farmacoData['observaciones'] ?? null,
                'farmacable_type' => get_class($cirugia),
                'farmacable_id' => $cirugia->id
            ]);
        }
    }

    private function getResultadoDisplay($resultado)
    {
        $resultados = [
            'satisfactorio' => 'Satisfactorio',
            'complicaciones' => 'Complicaciones',
            'estable' => 'Estable',
            'critico' => 'Crítico',
        ];

        return $resultados[$resultado] ?? $resultado;
    }

    private function getEstadoDisplay($estado)
    {
        $estados = [
            'recuperacion' => 'En recuperación',
            'alta' => 'Alta postoperatoria',
            'seguimiento' => 'Bajo seguimiento',
            'hospitalizado' => 'Hospitalizado',
        ];

        return $estados[$estado] ?? $estado;
    }
}