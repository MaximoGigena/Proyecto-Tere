<?php
// app/Http/Controllers/ControllersProcedimientos/DiagnosticoController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Diagnostico;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoDiagnostico;
use App\Models\ProcedimientoDiagnostico;
use App\Models\CentroVeterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\EnvioDocumentosService;
use Illuminate\Support\Facades\Validator;

class DiagnosticoController extends Controller
{

    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nuevo diagnóstico
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposDiagnostico = TipoDiagnostico::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('diagnosticos.create', compact('mascota', 'tiposDiagnostico', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nuevo diagnóstico
     */
    public function store(Request $request, $mascotaId)
    {
        try {
            Log::info('📥 Datos recibidos en store:', [
                'all_data' => $request->all(),
                'has_diferenciales' => $request->has('diagnosticos_diferenciales_seleccionados'),
                'diferenciales_raw' => $request->diagnosticos_diferenciales_seleccionados,
                'diferenciales_type' => gettype($request->diagnosticos_diferenciales_seleccionados)
            ]);

            // Preparar datos para validación
            $validationData = $request->all();
            
            // Procesar diagnósticos diferenciales si vienen como JSON string
            if ($request->has('diagnosticos_diferenciales_seleccionados')) {
                $diferencialesInput = $request->diagnosticos_diferenciales_seleccionados;
                
                // Si es un string, intentar decodificarlo como JSON
                if (is_string($diferencialesInput)) {
                    try {
                        $decoded = json_decode($diferencialesInput, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $validationData['diagnosticos_diferenciales_seleccionados'] = $decoded;
                            Log::info('✅ JSON decodificado correctamente:', [
                                'count' => count($decoded),
                                'data' => $decoded
                            ]);
                        } else {
                            Log::warning('⚠️ Error decodificando JSON:', [
                                'error' => json_last_error_msg(),
                                'raw' => $diferencialesInput
                            ]);
                            // Si no es JSON válido, establecer como array vacío
                            $validationData['diagnosticos_diferenciales_seleccionados'] = [];
                        }
                    } catch (\Exception $e) {
                        Log::error('❌ Excepción al decodificar JSON:', [
                            'error' => $e->getMessage(),
                            'raw' => $diferencialesInput
                        ]);
                        $validationData['diagnosticos_diferenciales_seleccionados'] = [];
                    }
                } elseif (!is_array($diferencialesInput)) {
                    // Si no es string ni array, establecer como array vacío
                    $validationData['diagnosticos_diferenciales_seleccionados'] = [];
                }
            }

            $validated = Validator::make($validationData, [
                'tipo_diagnostico_id' => 'required|exists:tipos_diagnostico,id',
                'nombre' => 'required|string|max:255',
                'fecha_diagnostico' => 'required|date',
                'estado' => 'required|in:activo,resuelto,cronico,seguimiento,sospecha',
                'examenes' => 'nullable|string',
                'conducta' => 'nullable|string',
                'observaciones' => 'nullable|string|max:500',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'costo' => 'nullable|numeric|min:0',
                'archivos.*' => 'nullable|file|max:10240',
                'medio_envio' => 'required|in:email,telegram,whatsapp',
                'mascota_id' => 'required|exists:mascotas,id',
                
                'diagnosticos_diferenciales_seleccionados' => 'nullable|array',
                'diagnosticos_diferenciales_seleccionados.*.id' => 'required|exists:tipos_diagnostico,id',
                'diagnosticos_diferenciales_seleccionados.*.nombre' => 'required|string|max:255',
                'diagnosticos_diferenciales_seleccionados.*.relevancia' => 'nullable|in:alta,media,baja',
            ])->validate();

            // Log de datos validados
            Log::info('✅ Datos validados:', [
                'tipo_diagnostico_id' => $validated['tipo_diagnostico_id'],
                'nombre' => $validated['nombre'],
                'diferenciales_count' => isset($validated['diagnosticos_diferenciales_seleccionados']) 
                    ? count($validated['diagnosticos_diferenciales_seleccionados']) 
                    : 0,
                'diferenciales_data' => $validated['diagnosticos_diferenciales_seleccionados'] ?? []
            ]);

            $diagnosticoCreado = null;
            $mascotaData = null;
            $diagnosticosDiferencialesGuardados = [];

            DB::transaction(function () use ($validated, $mascotaId, &$diagnosticoCreado, &$mascotaData, &$diagnosticosDiferencialesGuardados, $request) {
                // 1. Crear el registro específico de Diagnostico
                $diagnostico = Diagnostico::create([
                    'tipo_diagnostico_id' => $validated['tipo_diagnostico_id'],
                    'nombre' => $validated['nombre'],
                    'fecha_diagnostico' => $validated['fecha_diagnostico'],
                    'estado' => $validated['estado'],
                    'examenes_complementarios' => $validated['examenes'] ?? null,
                    'conducta_terapeutica' => $validated['conducta'] ?? null,
                    'observaciones' => $validated['observaciones'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'clinico',
                    'fecha_aplicacion' => $validated['fecha_diagnostico'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Asociar el diagnóstico con el proceso médico
                $diagnostico->procesoMedico()->save($procesoMedico);

                // 4. Guardar diagnósticos diferenciales en la tabla separada
                if (isset($validated['diagnosticos_diferenciales_seleccionados']) && 
                    is_array($validated['diagnosticos_diferenciales_seleccionados']) &&
                    count($validated['diagnosticos_diferenciales_seleccionados']) > 0) {
                    
                    $diagnosticosDiferencialesGuardados = $this->guardarDiagnosticosDiferenciales(
                        $diagnostico, 
                        $procesoMedico, 
                        $validated['diagnosticos_diferenciales_seleccionados']
                    );
                    
                    Log::info('📝 Diagnósticos diferenciales guardados:', [
                        'count' => count($diagnosticosDiferencialesGuardados),
                        'ids' => array_map(function($pd) {
                            return $pd->diagnostico_id;
                        }, $diagnosticosDiferencialesGuardados)
                    ]);
                }

                // 5. Manejar archivos adjuntos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($diagnostico, $request->file('archivos'));
                }

                // 6. Cargar relaciones para la respuesta
                $diagnosticoCreado = $diagnostico->load([
                    'tipoDiagnostico',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario',
                    'procedimientosDiagnosticos.diagnostico'
                ]);

                // 7. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 8. Enviar certificado PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($diagnosticoCreado && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoDiagnostico(
                        $diagnosticoCreado, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y certificado enviado';

                    Log::info('✅ Certificado de diagnóstico enviado exitosamente', [
                        'diagnostico_id' => $diagnosticoCreado->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';

                    Log::error('❌ Error enviando certificado de diagnóstico', [
                        'diagnostico_id' => $diagnosticoCreado->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('✅ Diagnóstico registrado exitosamente', [
                'diagnostico_id' => $diagnosticoCreado->id,
                'mascota_id' => $mascotaId,
                'usuario_id' => Auth::id(),
                'diagnosticos_diferenciales_count' => count($diagnosticosDiferencialesGuardados)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico registrado exitosamente' . $mensajeEnvio,
                'data' => [
                    'diagnostico' => $diagnosticoCreado,
                    'diagnosticos_diferenciales' => $diagnosticosDiferencialesGuardados,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar diagnóstico', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    private function guardarDiagnosticosDiferenciales($diagnosticoPrincipal, $procesoMedico, $diagnosticosDiferencialesArray)
    {
        $diagnosticosDiferencialesGuardados = [];
        $veterinarioId = Auth::id();

        if (!is_array($diagnosticosDiferencialesArray) || empty($diagnosticosDiferencialesArray)) {
            return $diagnosticosDiferencialesGuardados;
        }

        foreach ($diagnosticosDiferencialesArray as $diagnosticoDiferencial) {
            if (!isset($diagnosticoDiferencial['id'])) {
                Log::warning('⚠️ Diagnóstico diferencial sin ID:', $diagnosticoDiferencial);
                continue;
            }

            $tipoDiagnostico = TipoDiagnostico::find($diagnosticoDiferencial['id']);
            
            if (!$tipoDiagnostico) {
                Log::warning('⚠️ Tipo de diagnóstico no encontrado:', ['id' => $diagnosticoDiferencial['id']]);
                continue;
            }

            $relevanciaMap = [
                'alta' => 'primario',
                'media' => 'secundario', 
                'baja' => 'asociado'
            ];
            $relevanciaInput = $diagnosticoDiferencial['relevancia'] ?? 'media';
            $relevancia = $relevanciaMap[$relevanciaInput] ?? 'secundario';

            
            // USAR 'sospecha' en lugar de 'diferencial' para evitar la restricción CHECK
            // 'sospecha' ya está en la lista de estados permitidos por la restricción
            $estadoPermitido = 'sospecha';

            $procedimientoDiagnostico = ProcedimientoDiagnostico::create([
                'procedimiento_id' => $diagnosticoPrincipal->id,
                'procedimiento_type' => get_class($diagnosticoPrincipal),
                'diagnostico_id' => $tipoDiagnostico->id,
                'diagnostico_type' => get_class($tipoDiagnostico),
                'estado' => $estadoPermitido,
                'relevancia' => $relevancia,
                'nombre_diagnostico' => $diagnosticoDiferencial['nombre'] ?? $tipoDiagnostico->nombre,
                'veterinario_id' => $veterinarioId,
                'fecha_asociacion' => now(),
                'observaciones' => 'Diagnóstico diferencial considerado'
            ]);

            $diagnosticosDiferencialesGuardados[] = $procedimientoDiagnostico;
            
            Log::info('📋 Diagnóstico diferencial guardado:', [
                'id' => $procedimientoDiagnostico->id,
                'diagnostico_id' => $tipoDiagnostico->id,
                'nombre' => $diagnosticoDiferencial['nombre'] ?? $tipoDiagnostico->nombre,
                'estado' => $estadoPermitido
            ]);
        }

        return $diagnosticosDiferencialesGuardados;
    }

    /**
     * Listar todos los diagnósticos de una mascota
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

            // Obtener diagnósticos que NO estén en estado 'resuelto' (o 'baja' si usas ese)
            $diagnosticos = Diagnostico::with([
                    'tipoDiagnostico',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario'
                ])
                ->where('estado', '!=', 'resuelto') // ← FILTRAR POR ESTADO, NO POR 'activo'
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->orderBy('fecha_diagnostico', 'desc')
                ->get()
                ->map(function($diagnostico) {
                    return [
                        'id' => $diagnostico->id,
                        'nombre' => $diagnostico->nombre,
                        'tipo_diagnostico' => $diagnostico->tipoDiagnostico->nombre ?? 'Tipo no especificado',
                        'fecha_diagnostico' => $diagnostico->fecha_diagnostico,
                        'fecha_diagnostico_formateada' => $diagnostico->fecha_diagnostico->format('d/m/Y'),
                        'estado' => $diagnostico->estado, // ← ESTADO ACTUAL
                        'estado_display' => $this->getEstadoDisplay($diagnostico->estado),
                        'centro_veterinario' => $diagnostico->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                        'veterinario' => $diagnostico->procesoMedico->veterinario->name ?? 'No especificado',
                        'diagnosticos_diferenciales' => $diagnostico->diagnosticos_diferenciales,
                        'examenes_complementarios' => $diagnostico->examenes_complementarios,
                        'conducta_terapeutica' => $diagnostico->conducta_terapeutica,
                        'observaciones' => $diagnostico->observaciones,
                        'esta_activo' => $diagnostico->estaActivo(), // ← Esto se basa en el estado
                        'created_at' => $diagnostico->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $diagnosticos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener diagnósticos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los diagnósticos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un diagnóstico específico
     */

    public function show($mascotaId, $diagnosticoId): JsonResponse
    {
        Log::info('🔍 DiagnósticoController@show - Iniciando', [
            'mascota_id' => $mascotaId,
            'diagnostico_id' => $diagnosticoId,
            'usuario_id' => auth()->id(),
            'url_completa' => request()->fullUrl()
        ]);
        
        try {
            // 1. Verificar primero que la mascota existe
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                Log::warning('❌ Mascota no encontrada en DiagnósticoController@show', [
                    'mascota_id' => $mascotaId,
                    'usuario_actual' => auth()->id()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }
            
            Log::info('✅ Mascota encontrada:', [
                'mascota_id' => $mascota->id,
                'nombre' => $mascota->nombre
            ]);
            
            // 2. Buscar el diagnóstico con eager loading simplificado
            Log::info('🔍 Buscando diagnóstico con ID:', ['diagnostico_id' => $diagnosticoId]);
            
            $diagnostico = Diagnostico::with([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procesoMedico.mascota',
                'procedimientosDiagnosticos'
            ])->find($diagnosticoId);
            
            if (!$diagnostico) {
                Log::warning('❌ Diagnóstico no encontrado directamente', [
                    'diagnostico_id' => $diagnosticoId,
                    'total_diagnosticos_en_db' => Diagnostico::count()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Diagnóstico no encontrado'
                ], 404);
            }
            
            Log::info('✅ Diagnóstico encontrado directamente:', [
                'diagnostico_id' => $diagnostico->id,
                'nombre' => $diagnostico->nombre,
                'tipo_diagnostico_id' => $diagnostico->tipo_diagnostico_id,
                'tiene_proceso_medico' => !is_null($diagnostico->procesoMedico)
            ]);
            
            // 3. Verificar que el diagnóstico pertenece a la mascota
            if (!$diagnostico->procesoMedico) {
                Log::warning('❌ Diagnóstico no tiene proceso médico asociado', [
                    'diagnostico_id' => $diagnostico->id,
                    'diagnostico_proceso_medico' => $diagnostico->procesoMedico
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'El diagnóstico no tiene proceso médico asociado'
                ], 404);
            }
            
            if ($diagnostico->procesoMedico->mascota_id != $mascotaId) {
                Log::warning('❌ Diagnóstico no pertenece a la mascota solicitada', [
                    'diagnostico_id' => $diagnostico->id,
                    'diagnostico_mascota_id' => $diagnostico->procesoMedico->mascota_id,
                    'mascota_solicitada_id' => $mascotaId,
                    'proceso_medico_id' => $diagnostico->procesoMedico->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'El diagnóstico no pertenece a esta mascota'
                ], 403);
            }
            
            // 4. Cargar los diagnósticos asociados manualmente
            $diagnosticosDiferenciales = [];
            if ($diagnostico->procedimientosDiagnosticos->isNotEmpty()) {
                foreach ($diagnostico->procedimientosDiagnosticos as $pd) {
                    // Cargar el diagnóstico y sus relaciones según el tipo
                    if ($pd->diagnostico) {
                        // Si es un TipoDiagnostico, no tiene relación tipoDiagnostico
                        if ($pd->diagnostico_type === 'App\Models\TiposProcedimientos\TipoDiagnostico') {
                            $diagnosticoInfo = [
                                'id' => $pd->diagnostico->id,
                                'nombre' => $pd->diagnostico->nombre,
                                'descripcion' => $pd->diagnostico->descripcion,
                                'tipoDiagnostico' => null // TipoDiagnostico no tiene relación tipoDiagnostico
                            ];
                        } 
                        // Si es un Diagnostico, cargar su tipoDiagnostico
                        elseif ($pd->diagnostico_type === 'App\Models\ProcedimientosMedicos\Diagnostico') {
                            $pd->diagnostico->load('tipoDiagnostico');
                            $diagnosticoInfo = [
                                'id' => $pd->diagnostico->id,
                                'nombre' => $pd->diagnostico->nombre,
                                'descripcion' => $pd->diagnostico->descripcion,
                                'tipoDiagnostico' => $pd->diagnostico->tipoDiagnostico ? [
                                    'id' => $pd->diagnostico->tipoDiagnostico->id,
                                    'nombre' => $pd->diagnostico->tipoDiagnostico->nombre
                                ] : null
                            ];
                        }
                    } else {
                        $diagnosticoInfo = null;
                    }
                    
                    $diagnosticosDiferenciales[] = [
                        'id' => $pd->id,
                        'diagnostico_id' => $pd->diagnostico_id,
                        'diagnostico_type' => $pd->diagnostico_type,
                        'nombre_diagnostico' => $pd->nombre_diagnostico,
                        'relevancia' => $pd->relevancia,
                        'estado' => $pd->estado,
                        'observaciones' => $pd->observaciones,
                        'fecha_asociacion' => $pd->fecha_asociacion,
                        'diagnostico' => $diagnosticoInfo
                    ];
                }
            }
            
            // 5. Transformar los datos para la respuesta
            $diagnosticoArray = $diagnostico->toArray();
            
            // Agregar datos adicionales
            if ($diagnostico->procesoMedico && $diagnostico->procesoMedico->centroVeterinario) {
                $diagnosticoArray['centro_veterinario'] = $diagnostico->procesoMedico->centroVeterinario;
                $diagnosticoArray['centro_veterinario_id'] = $diagnostico->procesoMedico->centro_veterinario_id;
            } else {
                $diagnosticoArray['centro_veterinario'] = null;
                $diagnosticoArray['centro_veterinario_id'] = null;
            }
            
            $diagnosticoArray['fecha_diagnostico_formateada'] = $diagnostico->fecha_diagnostico instanceof \Carbon\Carbon 
                ? $diagnostico->fecha_diagnostico->format('d/m/Y')
                : $diagnostico->fecha_diagnostico;
                
            $diagnosticoArray['estado_display'] = $this->getEstadoDisplay($diagnostico->estado);
            $diagnosticoArray['esta_activo'] = $diagnostico->estaActivo();
            $diagnosticoArray['procedimiento_diagnosticos'] = $diagnosticosDiferenciales;
            $diagnosticoArray['diagnosticos_diferenciales'] = array_map(function($pd) {
                return [
                    'id' => $pd['diagnostico_id'] ?: 'custom-' . $pd['id'],
                    'nombre' => $pd['nombre_diagnostico'] ?: ($pd['diagnostico']['nombre'] ?? 'Sin nombre')
                ];
            }, $diagnosticosDiferenciales);
            
            // 6. Cargar archivos adjuntos
            $archivosPath = "diagnosticos/{$diagnostico->id}";
            $diagnosticoArray['archivos'] = Storage::exists($archivosPath) 
                ? Storage::files($archivosPath) 
                : [];
            
            Log::info('✅ Diagnóstico procesado exitosamente', [
                'diagnostico_id' => $diagnostico->id,
                'nombre' => $diagnostico->nombre,
                'diferenciales_count' => count($diagnosticosDiferenciales)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $diagnosticoArray
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en DiagnósticoController@show', [
                'mascota_id' => $mascotaId,
                'diagnostico_id' => $diagnosticoId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar diagnóstico
     */
    public function update(Request $request, $mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($diagnosticoId);

            Log::info('📥 Datos recibidos en update:', [
                'all_data' => $request->all(),
                'has_diferenciales' => $request->has('diagnosticos_diferenciales_seleccionados'),
                'diagnostico_id' => $diagnosticoId
            ]);

            // Preparar datos para validación
            $validationData = $request->all();
            
            // Procesar diagnósticos diferenciales si vienen como JSON string
            if ($request->has('diagnosticos_diferenciales_seleccionados')) {
                $diferencialesInput = $request->diagnosticos_diferenciales_seleccionados;
                
                if (is_string($diferencialesInput)) {
                    try {
                        $decoded = json_decode($diferencialesInput, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $validationData['diagnosticos_diferenciales_seleccionados'] = $decoded;
                        }
                    } catch (\Exception $e) {
                        Log::error('❌ Error decodificando JSON en update:', [
                            'error' => $e->getMessage(),
                            'raw' => $diferencialesInput
                        ]);
                    }
                }
            }

            $validated = Validator::make($validationData, [
                'tipo_diagnostico_id' => 'required|exists:tipos_diagnostico,id',
                'nombre' => 'required|string|max:255',
                'fecha_diagnostico' => 'required|date',
                'estado' => 'required|in:activo,resuelto,cronico,seguimiento,sospecha',
                'examenes' => 'nullable|string',
                'conducta' => 'nullable|string',
                'observaciones' => 'nullable|string|max:500',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'costo' => 'nullable|numeric|min:0',
                'archivos.*' => 'nullable|file|max:10240',
                'medio_envio' => 'required|in:email,telegram,whatsapp',
                'mascota_id' => 'required|exists:mascotas,id',
                
                'diagnosticos_diferenciales_seleccionados' => 'nullable|array',
                'diagnosticos_diferenciales_seleccionados.*.id' => 'required|exists:tipos_diagnostico,id',
                'diagnosticos_diferenciales_seleccionados.*.nombre' => 'required|string|max:255',
                'diagnosticos_diferenciales_seleccionados.*.relevancia' => 'nullable|in:primario,secundario,asociado',
            ])->validate();

            DB::transaction(function () use ($validated, $diagnostico, $request, $diagnosticoId) {
                // 1. Actualizar el diagnóstico
                $diagnostico->update([
                    'tipo_diagnostico_id' => $validated['tipo_diagnostico_id'],
                    'nombre' => $validated['nombre'],
                    'fecha_diagnostico' => $validated['fecha_diagnostico'],
                    'estado' => $validated['estado'],
                    'examenes_complementarios' => $validated['examenes'] ?? $diagnostico->examenes_complementarios,
                    'conducta_terapeutica' => $validated['conducta'] ?? $diagnostico->conducta_terapeutica,
                    'observaciones' => $validated['observaciones'] ?? $diagnostico->observaciones,
                ]);

                // 2. Actualizar el proceso médico asociado
                if ($diagnostico->procesoMedico) {
                    $diagnostico->procesoMedico->update([
                        'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? $diagnostico->procesoMedico->centro_veterinario_id,
                        'fecha_aplicacion' => $validated['fecha_diagnostico'],
                        'observaciones' => $validated['observaciones'] ?? $diagnostico->procesoMedico->observaciones,
                        'costo' => $validated['costo'] ?? $diagnostico->procesoMedico->costo,
                    ]);
                }

                // 3. Eliminar diagnósticos diferenciales existentes
                $diagnostico->procedimientosDiagnosticos()->delete();
                
                Log::info('🗑️ Diagnósticos diferenciales eliminados para diagnóstico:', [
                    'diagnostico_id' => $diagnosticoId,
                    'count' => $diagnostico->procedimientosDiagnosticos()->count()
                ]);

                // 4. Crear nuevos diagnósticos diferenciales
                if (isset($validated['diagnosticos_diferenciales_seleccionados']) && 
                    is_array($validated['diagnosticos_diferenciales_seleccionados']) &&
                    count($validated['diagnosticos_diferenciales_seleccionados']) > 0) {
                    
                    $this->guardarDiagnosticosDiferenciales(
                        $diagnostico, 
                        $diagnostico->procesoMedico, 
                        $validated['diagnosticos_diferenciales_seleccionados']
                    );
                }

                // 5. Manejar eliminación de archivos
                if (isset($validated['archivos_a_eliminar'])) {
                    $this->eliminarArchivos($diagnostico, $validated['archivos_a_eliminar']);
                }

                // 6. Manejar nuevos archivos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($diagnostico, $request->file('archivos'));
                }
            });

            // Recargar relaciones
            $diagnostico->load([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procedimientosDiagnosticos.diagnostico'
            ]);

            Log::info('✅ Diagnóstico actualizado exitosamente', [
                'diagnostico_id' => $diagnostico->id,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico actualizado exitosamente',
                'data' => $diagnostico
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar diagnóstico', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dar de baja lógica a un diagnóstico
     */
    public function bajaLogica($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            // Verificar que la mascota exista primero
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Buscar el diagnóstico directamente
            $diagnostico = Diagnostico::find($diagnosticoId);
            if (!$diagnostico) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diagnóstico no encontrado'
                ], 404);
            }

            // Verificar que el diagnóstico pertenece a la mascota
            if (!$diagnostico->procesoMedico || $diagnostico->procesoMedico->mascota_id != $mascotaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El diagnóstico no pertenece a esta mascota'
                ], 403);
            }

            DB::transaction(function () use ($diagnostico) {
                // 1. SIMPLEMENTE CAMBIAR EL ESTADO A 'resuelto' o 'baja'
                $diagnostico->update([
                    'estado' => 'resuelto' // O 'baja' si prefieres un estado específico
                ]);
                
                // 2. OPCIONAL: Actualizar el proceso médico si tiene campo estado
                if ($diagnostico->procesoMedico) {
                    // Si el modelo ProcesoMedico tiene campo estado
                    if (isset($diagnostico->procesoMedico->estado)) {
                        $diagnostico->procesoMedico->update(['estado' => 'completado']);
                    }
                }
                
                // 3. OPCIONAL: Marcar diagnósticos diferenciales como resueltos
                if ($diagnostico->procedimientosDiagnosticos()->exists()) {
                    $diagnostico->procedimientosDiagnosticos()->update(['estado' => 'resuelto']);
                }

                Log::info('✅ Diagnóstico dado de baja (estado cambiado)', [
                    'diagnostico_id' => $diagnostico->id,
                    'mascota_id' => $diagnostico->procesoMedico->mascota_id,
                    'usuario_id' => Auth::id(),
                    'nuevo_estado' => 'resuelto'
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico dado de baja exitosamente',
                'data' => [
                    'id' => $diagnostico->id,
                    'estado' => 'resuelto', // Confirmar el nuevo estado
                    'esta_activo' => false // Esto ahora siempre será false
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al dar de baja el diagnóstico', [
                'diagnostico_id' => $diagnosticoId,
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar diagnósticos para el selector
     */
    public function buscarDiagnosticosParaSelector(Request $request): JsonResponse
    {
        try {
            $query = TipoDiagnostico::query();
            
            // Filtro por búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('nombre', 'like', "%{$searchTerm}%")
                    ->orWhere('descripcion', 'like', "%{$searchTerm}%")
                    ->orWhere('sintomas_caracteristicos', 'like', "%{$searchTerm}%");
                });
            }
            
            // Filtro por evolución
            if ($request->has('evolucion') && !empty($request->evolucion)) {
                $query->where('evolucion', $request->evolucion);
            }
            
            // Filtro por clasificación
            if ($request->has('clasificacion') && !empty($request->clasificacion)) {
                $query->where('clasificacion', $request->clasificacion);
            }
            
            // Filtrar por especie si se especifica
            if ($request->has('especie') && !empty($request->especie)) {
                $query->whereJsonContains('especies', $request->especie);
            }
            
            $diagnosticos = $query->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $diagnosticos->items(),
                'pagination' => [
                    'total' => $diagnosticos->total(),
                    'per_page' => $diagnosticos->perPage(),
                    'current_page' => $diagnosticos->currentPage(),
                    'last_page' => $diagnosticos->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al buscar diagnósticos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar diagnósticos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar diagnóstico
     */
    public function destroy($mascotaId, $diagnosticoId): JsonResponse
    {
        // Redirigir al método de baja lógica
        return $this->bajaLogica($mascotaId, $diagnosticoId);
    }

    // Agrega este método temporal en el controlador para depurar
    public function debugDiagnostico($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::with([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procedimientosDiagnosticos' => function($query) {
                    $query->with(['diagnostico' => function($q) {
                        $q->with('tipoDiagnostico');
                    }]);
                }
            ])
            ->findOrFail($diagnosticoId);

            // Obtener los procedimientos de diagnóstico directamente
            $procedimientosDirectos = ProcedimientoDiagnostico::where([
                'procedimiento_id' => $diagnostico->id,
                'procedimiento_type' => get_class($diagnostico)
            ])->with('diagnostico.tipoDiagnostico')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'diagnostico' => $diagnostico,
                    'procedimientos_via_relacion' => $diagnostico->procedimientosDiagnosticos,
                    'procedimientos_directos' => $procedimientosDirectos,
                    'relaciones_cargadas' => array_keys($diagnostico->getRelations())
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar diagnóstico como resuelto
     */
    public function marcarComoResuelto($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($diagnosticoId);

            $diagnostico->marcarComoResuelto();

            Log::info('✅ Diagnóstico marcado como resuelto', [
                'diagnostico_id' => $diagnosticoId,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico marcado como resuelto exitosamente',
                'data' => $diagnostico
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al marcar diagnóstico como resuelto', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el diagnóstico como resuelto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de diagnósticos de una mascota
     */
    public function estadisticas($mascotaId): JsonResponse
    {
        try {
            $estadisticas = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->get()
            ->pluck('cantidad', 'estado');

            $total = $estadisticas->sum();
            $activos = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->activos()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'activos' => $activos,
                    'por_estado' => $estadisticas,
                    'estados_display' => Diagnostico::getEstadosDropdown()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de diagnósticos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo($diagnosticoId, $nombreArchivo)
    {
        try {
            $diagnostico = Diagnostico::findOrFail($diagnosticoId);
            $path = "diagnosticos/{$diagnostico->id}/{$nombreArchivo}";

            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return Storage::download($path);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo de diagnóstico', [
                'diagnostico_id' => $diagnosticoId,
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
     * Obtener diagnósticos diferenciales de un diagnóstico
     */
    public function getDiagnosticosDiferenciales($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($diagnosticoId);

            $diagnosticosDiferenciales = $diagnostico->procedimientosDiagnosticos()
                ->with('diagnostico')
                ->where('estado', 'diferencial')
                ->get()
                ->map(function($pd) {
                    return [
                        'id' => $pd->id,
                        'nombre_diagnostico' => $pd->nombre_diagnostico,
                        'relevancia' => $pd->relevancia,
                        'estado' => $pd->estado,
                        'observaciones' => $pd->observaciones,
                        'fecha_asociacion' => $pd->fecha_asociacion,
                        'diagnostico' => $pd->diagnostico,
                        'es_custom' => is_null($pd->diagnostico_id)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $diagnosticosDiferenciales
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener diagnósticos diferenciales', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener diagnósticos diferenciales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Métodos auxiliares privados
     */
    
    private function guardarArchivos($diagnostico, $archivos)
    {
        $path = "diagnosticos/{$diagnostico->id}";
        
        foreach ($archivos as $archivo) {
            if ($archivo && $archivo->isValid()) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $extension = $archivo->getClientOriginalExtension();
                $nombreUnico = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
                
                Storage::putFileAs($path, $archivo, $nombreUnico);
                
                Log::info('✅ Archivo guardado para diagnóstico', [
                    'diagnostico_id' => $diagnostico->id,
                    'archivo' => $nombreUnico,
                    'original' => $nombreOriginal
                ]);
            }
        }
    }

    private function eliminarArchivos($diagnostico, $nombresArchivos)
    {
        foreach ($nombresArchivos as $nombreArchivo) {
            $path = "diagnosticos/{$diagnostico->id}/{$nombreArchivo}";
            if (Storage::exists($path)) {
                Storage::delete($path);
                
                Log::info('✅ Archivo eliminado de diagnóstico', [
                    'diagnostico_id' => $diagnostico->id,
                    'archivo' => $nombreArchivo
                ]);
            }
        }
    }

    private function getEstadoDisplay($estado)
    {
        $estados = [
            'activo' => 'Activo',
            'resuelto' => 'Resuelto',
            'cronico' => 'Crónico',
            'seguimiento' => 'En seguimiento',
            'sospecha' => 'Sospecha',
        ];

        return $estados[$estado] ?? $estado;
    }
}