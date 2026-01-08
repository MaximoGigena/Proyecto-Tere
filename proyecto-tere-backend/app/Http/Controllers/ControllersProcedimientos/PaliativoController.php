<?php
// app/Http/Controllers/ControllersProcedimientos/PaliativoController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\CuidadoPaliativo;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoPaliativo;
use App\Models\CentroVeterinario;
use App\Models\FarmacoAsociado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\EnvioDocumentosService;

class PaliativoController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nuevo procedimiento paliativo
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposPaliativo = TipoPaliativo::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('paliativos.create', compact('mascota', 'tiposPaliativo', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nuevo procedimiento paliativo
     */
    public function store(Request $request, $mascotaId): JsonResponse
    {
        // Validación según los campos del formulario Vue
        $validated = $request->validate([
            // Campos obligatorios del modelo CuidadoPaliativo
            'tipo_procedimiento_id' => 'required|exists:tipos_paliativo,id',
            'fecha_inicio' => 'required|date',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'resultado' => 'required|in:mejoria,alivio,estabilizacion,sin_cambio,empeoramiento',
            'estado' => 'required|in:estable,dolor_controlado,dolor_parcial,deterioro,critico',
            
            // Campos opcionales
            'frecuencia_valor' => 'nullable|integer|min:1',
            'frecuencia_unidad' => 'nullable|in:horas,dias,semanas,meses',
            'fecha_control' => 'nullable|date',
            'descripcion' => 'nullable|string|max:1000',
            'medicacion_notas' => 'nullable|string|max:500',
            'recomendaciones' => 'nullable|string|max:500',
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
            'farmacos_asociados.*.momento_aplicacion' => 'required|in:inicio,mantenimiento,rescue,final',
            
            // Archivos adjuntos
            'archivos.*' => 'nullable|file|max:10240', // 10MB máximo
        ]);

        try {
            $paliativoCreado = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$paliativoCreado, &$mascotaData, $request) {
                // 1. Crear el registro específico de CuidadoPaliativo
                $paliativo = CuidadoPaliativo::create([
                    'tipo_paliativo_id' => $validated['tipo_procedimiento_id'],
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'resultado' => $validated['resultado'],
                    'estado_mascota' => $validated['estado'],
                    'diagnostico_base' => $this->procesarDiagnosticosBase($validated['diagnosticos'] ?? []),
                    'frecuencia_valor' => $validated['frecuencia_valor'] ?? null,
                    'frecuencia_unidad' => $validated['frecuencia_unidad'] ?? null,
                    'medicacion_complementaria' => $validated['medicacion_notas'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones'] ?? null,
                    'observaciones' => $validated['descripcion'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'clinico',
                    'fecha_aplicacion' => $validated['fecha_inicio'],
                    'observaciones' => $validated['descripcion'] ?? null,
                    'costo' => null,
                ]);

                // 3. Asociar el paliativo con el proceso médico
                $paliativo->procesoMedico()->save($procesoMedico);

                // 4. Guardar fármacos asociados (si existen)
                if (isset($validated['farmacos_asociados']) && is_array($validated['farmacos_asociados'])) {
                    $this->guardarFarmacosAsociados($paliativo, $validated['farmacos_asociados']);
                }

                // 5. Manejar archivos adjuntos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($paliativo, $request->file('archivos'));
                }

                // 6. Cargar relaciones para la respuesta
                $paliativoCreado = $paliativo->load([
                    'tipoPaliativo',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario',
                    'farmacosAsociados.tipoFarmaco'
                ]);
                
                // 7. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 8. Enviar certificado PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($paliativoCreado && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoPaliativo(
                        $paliativoCreado, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y certificado enviado';
                    
                    Log::info('✅ Certificado de procedimiento paliativo enviado exitosamente', [
                        'paliativo_id' => $paliativoCreado->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando certificado de procedimiento paliativo', [
                        'paliativo_id' => $paliativoCreado->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('✅ Procedimiento paliativo registrado exitosamente', [
                'paliativo_id' => $paliativoCreado->id,
                'mascota_id' => $mascotaId,
                'usuario_id' => Auth::id(),
                'farmacos_asociados' => count($validated['farmacos_asociados'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Procedimiento paliativo registrado exitosamente' . $mensajeEnvio,
                'data' => [
                    'paliativo' => $paliativoCreado,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar procedimiento paliativo', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los procedimientos paliativos de una mascota
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

            // Obtener los paliativos con sus relaciones
            $paliativos = CuidadoPaliativo::with([
                'tipoPaliativo',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'farmacosAsociados.tipoFarmaco'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->map(function($paliativo) {
                return [
                    'id' => $paliativo->id,
                    'tipo_paliativo' => $paliativo->tipoPaliativo->nombre ?? 'Tipo no especificado',
                    'fecha_inicio' => $paliativo->fecha_inicio,
                    'fecha_inicio_formateada' => $paliativo->fecha_inicio_formateada ?? $paliativo->fecha_inicio->format('d/m/Y H:i'),
                    'resultado' => $paliativo->resultado,
                    'resultado_display' => $this->getResultadoDisplay($paliativo->resultado),
                    'estado_mascota' => $paliativo->estado_mascota,
                    'estado_display' => $this->getEstadoDisplay($paliativo->estado_mascota),
                    'diagnostico_base' => $paliativo->diagnostico_base,
                    'centro_veterinario' => $paliativo->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $paliativo->procesoMedico->veterinario->name ?? 'No especificado',
                    'frecuencia_valor' => $paliativo->frecuencia_valor,
                    'frecuencia_unidad' => $paliativo->frecuencia_unidad,
                    'frecuencia_completa' => $paliativo->frecuencia_completa,
                    'fecha_control' => $paliativo->fecha_control,
                    'fecha_control_formateada' => $paliativo->fecha_control ? $paliativo->fecha_control->format('d/m/Y') : null,
                    'observaciones' => $paliativo->observaciones,
                    'medicacion_complementaria' => $paliativo->medicacion_complementaria,
                    'recomendaciones_tutor' => $paliativo->recomendaciones_tutor,
                    'farmacos_asociados' => $paliativo->farmacosAsociados->map(function($farmaco) {
                        return [
                            'id' => $farmaco->id,
                            'nombre_comercial' => $farmaco->tipoFarmaco->nombre_comercial ?? 'Fármaco no especificado',
                            'nombre_generico' => $farmaco->tipoFarmaco->nombre_generico ?? null,
                            'dosis_prescrita' => $farmaco->dosis_prescrita,
                            'unidad_dosis' => $farmaco->unidad_dosis,
                            'momento_aplicacion' => $farmaco->momento_aplicacion,
                            'momento_aplicacion_texto' => $farmaco->momento_aplicacion_texto,
                            'observaciones' => $farmaco->observaciones
                        ];
                    }),
                    'created_at' => $paliativo->created_at,
                    'requiere_seguimiento_frecuente' => $paliativo->requiereSeguimientoFrecuente(),
                    'proxima_aplicacion' => $paliativo->calcularProximaAplicacion(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $paliativos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener procedimientos paliativos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los procedimientos paliativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un procedimiento paliativo específico
     */
    public function show($mascotaId, $paliativoId): JsonResponse
    {
        try {
            $paliativo = CuidadoPaliativo::with([
                'tipoPaliativo',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procesoMedico.mascota',
                'farmacosAsociados.tipoFarmaco'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->findOrFail($paliativoId);

            // Cargar archivos adjuntos si existen
            $archivos = [];
            $archivosPath = "paliativos/{$paliativo->id}";
            if (Storage::exists($archivosPath)) {
                $archivos = Storage::files($archivosPath);
            }

            $paliativoArray = $paliativo->toArray();
            $paliativoArray['fecha_inicio_formateada'] = $paliativo->fecha_inicio->format('d/m/Y H:i');
            $paliativoArray['resultado_display'] = $this->getResultadoDisplay($paliativo->resultado);
            $paliativoArray['estado_display'] = $this->getEstadoDisplay($paliativo->estado_mascota);
            $paliativoArray['frecuencia_completa'] = $paliativo->frecuencia_completa;
            $paliativoArray['fecha_control_formateada'] = $paliativo->fecha_control ? $paliativo->fecha_control->format('d/m/Y') : null;
            $paliativoArray['archivos'] = $archivos;
            $paliativoArray['medicacion_complementaria_array'] = $paliativo->medicacion_complementaria_array;
            $paliativoArray['farmacos_asociados'] = $paliativo->farmacosAsociados->map(function($farmaco) {
                return array_merge($farmaco->toArray(), [
                    'farmaco_nombre_comercial' => $farmaco->tipoFarmaco->nombre_comercial ?? null,
                    'farmaco_nombre_generico' => $farmaco->tipoFarmaco->nombre_generico ?? null,
                    'momento_aplicacion_texto' => $farmaco->momento_aplicacion_texto,
                    'dosis_formateada' => $farmaco->dosis_formateada,
                    'frecuencia_completa' => $farmaco->frecuencia_completa,
                    'duracion_completa' => $farmaco->duracion_completa
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $paliativoArray
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener procedimiento paliativo:', [
                'paliativo_id' => $paliativoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el procedimiento paliativo: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar procedimiento paliativo
     */
    public function update(Request $request, $mascotaId, $paliativoId): JsonResponse
    {
        try {
            $paliativo = CuidadoPaliativo::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($paliativoId);

            $validated = $request->validate([
                // Campos actualizables
                'tipo_procedimiento_id' => 'sometimes|exists:tipos_paliativo,id',
                'fecha_inicio' => 'sometimes|date',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'resultado' => 'sometimes|in:mejoria,alivio,estabilizacion,sin_cambio,empeoramiento',
                'estado' => 'sometimes|in:estable,dolor_controlado,dolor_parcial,deterioro,critico',
                'frecuencia_valor' => 'nullable|integer|min:1',
                'frecuencia_unidad' => 'nullable|in:horas,dias,semanas,meses',
                'fecha_control' => 'nullable|date',
                'descripcion' => 'nullable|string|max:1000',
                'medicacion_notas' => 'nullable|string|max:500',
                'recomendaciones' => 'nullable|string|max:500',
                
                // Fármacos asociados
                'farmacos_asociados' => 'nullable|array',
                'farmacos_asociados.*.farmaco_id' => 'required_with:farmacos_asociados|exists:tipos_farmaco,id',
                'farmacos_asociados.*.dosis' => 'required_with:farmacos_asociados|numeric|min:0.001',
                'farmacos_asociados.*.frecuencia' => 'nullable|string',
                'farmacos_asociados.*.duracion' => 'nullable|string',
                'farmacos_asociados.*.observaciones' => 'nullable|string|max:1000',
                'farmacos_asociados.*.momento_aplicacion' => 'required_with:farmacos_asociados|in:inicio,mantenimiento,rescue,final',
                
                // Archivos
                'archivos.*' => 'nullable|file|max:10240',
                'archivos_a_eliminar' => 'nullable|array',
                'archivos_a_eliminar.*' => 'string',
            ]);

            DB::transaction(function () use ($validated, $paliativo, $request) {
                // 1. Actualizar el paliativo
                $paliativo->update([
                    'tipo_paliativo_id' => $validated['tipo_procedimiento_id'] ?? $paliativo->tipo_paliativo_id,
                    'fecha_inicio' => $validated['fecha_inicio'] ?? $paliativo->fecha_inicio,
                    'resultado' => $validated['resultado'] ?? $paliativo->resultado,
                    'estado_mascota' => $validated['estado'] ?? $paliativo->estado_mascota,
                    'frecuencia_valor' => $validated['frecuencia_valor'] ?? $paliativo->frecuencia_valor,
                    'frecuencia_unidad' => $validated['frecuencia_unidad'] ?? $paliativo->frecuencia_unidad,
                    'medicacion_complementaria' => $validated['medicacion_notas'] ?? $paliativo->medicacion_complementaria,
                    'recomendaciones_tutor' => $validated['recomendaciones'] ?? $paliativo->recomendaciones_tutor,
                    'observaciones' => $validated['descripcion'] ?? $paliativo->observaciones,
                ]);

                // 2. Actualizar el proceso médico asociado
                if ($paliativo->procesoMedico) {
                    $paliativo->procesoMedico->update([
                        'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? $paliativo->procesoMedico->centro_veterinario_id,
                        'fecha_aplicacion' => $validated['fecha_inicio'] ?? $paliativo->fecha_inicio,
                        'observaciones' => $validated['descripcion'] ?? $paliativo->observaciones,
                    ]);
                }

                // 3. Actualizar fármacos asociados
                if (isset($validated['farmacos_asociados'])) {
                    // Eliminar fármacos existentes
                    $paliativo->farmacosAsociados()->delete();
                    
                    // Crear nuevos fármacos
                    $this->guardarFarmacosAsociados($paliativo, $validated['farmacos_asociados']);
                }

                // 4. Manejar eliminación de archivos
                if (isset($validated['archivos_a_eliminar'])) {
                    $this->eliminarArchivos($paliativo, $validated['archivos_a_eliminar']);
                }

                // 5. Manejar nuevos archivos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($paliativo, $request->file('archivos'));
                }
            });

            // Recargar relaciones
            $paliativo->load([
                'tipoPaliativo',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'farmacosAsociados.tipoFarmaco'
            ]);

            Log::info('✅ Procedimiento paliativo actualizado exitosamente', [
                'paliativo_id' => $paliativo->id,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Procedimiento paliativo actualizado exitosamente',
                'data' => $paliativo
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar procedimiento paliativo', [
                'paliativo_id' => $paliativoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar procedimiento paliativo
     */
    public function destroy($mascotaId, $paliativoId): JsonResponse
    {
        try {
            $paliativo = CuidadoPaliativo::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($paliativoId);

            DB::transaction(function () use ($paliativo) {
                // 1. Eliminar archivos adjuntos
                $archivosPath = "paliativos/{$paliativo->id}";
                if (Storage::exists($archivosPath)) {
                    Storage::deleteDirectory($archivosPath);
                }

                // 2. Eliminar fármacos asociados
                $paliativo->farmacosAsociados()->delete();

                // 3. Eliminar el proceso médico (si existe)
                if ($paliativo->procesoMedico) {
                    $paliativo->procesoMedico->delete();
                }

                // 4. Eliminar el paliativo
                $paliativo->delete();
            });

            Log::info('✅ Procedimiento paliativo eliminado exitosamente', [
                'paliativo_id' => $paliativoId,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Procedimiento paliativo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar procedimiento paliativo', [
                'paliativo_id' => $paliativoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de procedimientos paliativos de una mascota
     */
    public function estadisticas($mascotaId): JsonResponse
    {
        try {
            $estadisticas = CuidadoPaliativo::whereHas('procesoMedico', function($query) use ($mascotaId) {
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
                    'resultados_display' => CuidadoPaliativo::getResultadosPermitidos(),
                    'estados_display' => CuidadoPaliativo::getEstadosMascotaPermitidos()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de procedimientos paliativos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo($paliativoId, $nombreArchivo)
    {
        try {
            $paliativo = CuidadoPaliativo::findOrFail($paliativoId);
            $path = "paliativos/{$paliativo->id}/{$nombreArchivo}";

            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return Storage::download($path);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo de procedimiento paliativo', [
                'paliativo_id' => $paliativoId,
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
     * Métodos auxiliares privados
     */
    
    private function guardarArchivos($paliativo, $archivos)
    {
        $path = "paliativos/{$paliativo->id}";
        
        foreach ($archivos as $archivo) {
            if ($archivo && $archivo->isValid()) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $extension = $archivo->getClientOriginalExtension();
                $nombreUnico = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
                
                Storage::putFileAs($path, $archivo, $nombreUnico);
                
                Log::info('✅ Archivo guardado para procedimiento paliativo', [
                    'paliativo_id' => $paliativo->id,
                    'archivo' => $nombreUnico,
                    'original' => $nombreOriginal
                ]);
            }
        }
    }

    private function eliminarArchivos($paliativo, $nombresArchivos)
    {
        foreach ($nombresArchivos as $nombreArchivo) {
            $path = "paliativos/{$paliativo->id}/{$nombreArchivo}";
            if (Storage::exists($path)) {
                Storage::delete($path);
                
                Log::info('✅ Archivo eliminado de procedimiento paliativo', [
                    'paliativo_id' => $paliativo->id,
                    'archivo' => $nombreArchivo
                ]);
            }
        }
    }

    private function procesarDiagnosticosBase($diagnosticosIds)
    {
        if (empty($diagnosticosIds)) {
            return 'Sin diagnóstico específico';
        }

        // Aquí podrías cargar los nombres de los diagnósticos
        // Por ahora, simplemente devolvemos un texto con los IDs
        return 'Diagnósticos asociados: ' . implode(', ', $diagnosticosIds);
    }

    private function guardarFarmacosAsociados($paliativo, $farmacosData)
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

            $paliativo->farmacosAsociados()->create([
                'tipo_farmaco_id' => $farmacoData['farmaco_id'],
                'dosis_prescrita' => $dosisPrescrita,
                'unidad_dosis' => $unidadDosis,
                'es_dosis_unica' => false, // Para cuidados paliativos generalmente son tratamientos prolongados
                'frecuencia_valor' => $frecuenciaValor,
                'frecuencia_unidad' => $frecuenciaUnidad,
                'duracion_valor' => $duracionValor,
                'duracion_unidad' => $duracionUnidad,
                'momento_aplicacion' => $farmacoData['momento_aplicacion'],
                'observaciones' => $farmacoData['observaciones'] ?? null,
                'farmacable_type' => get_class($paliativo),
                'farmacable_id' => $paliativo->id
            ]);
        }
    }

    private function getResultadoDisplay($resultado)
    {
        $resultados = [
            'mejoria' => 'Mejoría evidente',
            'alivio' => 'Alivio parcial',
            'estabilizacion' => 'Estabilización',
            'sin_cambio' => 'Sin cambios',
            'empeoramiento' => 'Empeoramiento',
        ];

        return $resultados[$resultado] ?? $resultado;
    }

    private function getEstadoDisplay($estado)
    {
        $estados = [
            'estable' => 'Estable',
            'dolor_controlado' => 'Con dolor controlado',
            'dolor_parcial' => 'Con dolor parcialmente controlado',
            'deterioro' => 'En deterioro',
            'critico' => 'Crítico',
        ];

        return $estados[$estado] ?? $estado;
    }
}