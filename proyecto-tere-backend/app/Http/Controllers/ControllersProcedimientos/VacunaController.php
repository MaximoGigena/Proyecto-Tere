<?php
// app/Http/Controllers/ControllersProcedimientos/VacunaController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoVacuna;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;
use App\Http\Requests\StoreVacunaRequest;
use App\Http\Requests\UpdateVacunaRequest;
use App\Services\Validaciones\VacunaValidationService;

class VacunaController extends Controller
{
    protected $envioDocumentosService;
    protected $validationService;

    public function __construct(
        EnvioDocumentosService $envioDocumentosService,
        VacunaValidationService $validationService // Inyectar el servicio de validación
    ) {
        $this->envioDocumentosService = $envioDocumentosService;
        $this->validationService = $validationService;
    }

    /**
     * Mostrar formulario para crear nueva vacuna
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposVacuna = TipoVacuna::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('vacunas.create', compact('mascota', 'tiposVacuna', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nueva vacuna
     */
    public function store(StoreVacunaRequest $request, $mascotaId)
    {
        // Validación según los campos del caso de uso
         $validated = $request->validated();

        try {
            $vacunaCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$vacunaCreada, &$mascotaData) {
                // 1. Crear el registro específico de Vacuna
                $vacuna = Vacuna::create([
                    'tipo_vacuna_id' => $validated['tipo_vacuna_id'],
                    'numero_dosis' => $validated['numero_dosis'],
                    'lote_serie' => $validated['lote_serie'],
                    'fecha_proxima_dosis' => $validated['fecha_proxima_dosis'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'preventivo',
                    'fecha_aplicacion' => $validated['fecha_aplicacion'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Asociar la vacuna con el proceso médico
                $vacuna->procesoMedico()->save($procesoMedico);
                
                // 4. Cargar relaciones para el PDF
                $vacunaCreada = $vacuna->load([
                    'tipo',
                    'procesoMedico.centroVeterinario'
                ]);
                
                // 5. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 6. Enviar certificado PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($vacunaCreada && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoVacuna(
                        $vacunaCreada, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y certificado enviado';
                    
                    Log::info('✅ Certificado de vacuna enviado exitosamente', [
                        'vacuna_id' => $vacunaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando certificado de vacuna', [
                        'vacuna_id' => $vacunaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // DEVUELVE JSON PARA API
            return response()->json([
                'success' => true,
                'message' => 'Vacuna registrada exitosamente' . $mensajeEnvio,
                'data' => [
                    'vacuna' => $vacunaCreada,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar vacuna', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // DEVUELVE JSON ERROR PARA API
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la vacuna: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una vacuna específica para edición
     */
    public function show($id): JsonResponse
    {
        try {
            Log::info('🔍 Buscando vacuna con ID:', ['id' => $id]);
            
            $vacuna = Vacuna::with([
                'tipo',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])->find($id);

            if (!$vacuna) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vacuna no encontrada'
                ], 404);
            }

            // Verificar permisos - ejemplo básico
            $user = auth()->user();
            $veterinarioId = $vacuna->procesoMedico->veterinario_id ?? null;
            
            if ($veterinarioId !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para editar esta vacuna'
                ], 403);
            }

            $vacunaData = [
                'id' => $vacuna->id,
                'tipo_vacuna_id' => $vacuna->tipo_vacuna_id,
                'nombre' => $vacuna->tipo->nombre ?? 'Vacuna no especificada',
                'numero_dosis' => $vacuna->numero_dosis,
                'lote_serie' => $vacuna->lote_serie,
                'fecha_aplicacion' => $vacuna->procesoMedico->fecha_aplicacion ?? null,
                'fecha_proxima_dosis' => $vacuna->fecha_proxima_dosis,
                'centro_veterinario_id' => $vacuna->procesoMedico->centro_veterinario_id ?? null,
                'observaciones' => $vacuna->procesoMedico->observaciones ?? null,
                'costo' => $vacuna->procesoMedico->costo ?? null,
                'created_at' => $vacuna->created_at,
                'updated_at' => $vacuna->updated_at
            ];

            return response()->json([
                'success' => true,
                'data' => $vacunaData
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener vacuna:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener la vacuna'
            ], 500);
        }
    }

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

            // Obtener las vacunas con sus relaciones
            $vacunas = Vacuna::with([
                'tipo',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
             ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->activas() // Añade este scope para solo traer activas
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($vacuna) {
                return [
                    'id' => $vacuna->id,
                    'nombre' => $vacuna->tipo->nombre ?? 'Vacuna no especificada',
                    'tipo_vacuna_id' => $vacuna->tipo_vacuna_id,
                    'numero_dosis' => $vacuna->numero_dosis,
                    'lote_serie' => $vacuna->lote_serie,
                    'fecha_aplicacion' => $vacuna->procesoMedico->fecha_aplicacion ?? null,
                    'fecha_proxima_dosis' => $vacuna->fecha_proxima_dosis,
                    'centro_veterinario' => $vacuna->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $vacuna->procesoMedico->veterinario->name ?? 'No especificado',
                    'observaciones' => $vacuna->procesoMedico->observaciones,
                    'created_at' => $vacuna->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $vacunas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener vacunas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las vacunas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar vacuna
     */
    public function edit(Vacuna $vacuna)
    {
        $vacuna->load('procesoMedico');
        $tiposVacuna = TipoVacuna::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('vacunas.edit', compact('vacuna', 'tiposVacuna', 'centrosVeterinarios'));
    }

    /**
     * Actualizar vacuna (API)
     */
    public function updateApi(Request $request, $id): JsonResponse
    {
        try {
            // Buscar la vacuna a actualizar
            $vacuna = Vacuna::with('procesoMedico.mascota')->findOrFail($id);
            
            // Verificar permisos
            $user = auth()->user();
            $veterinarioId = $vacuna->procesoMedico->veterinario_id ?? null;
            
            if ($veterinarioId !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para editar esta vacuna'
                ], 403);
            }

            // Validación de campos básicos
            $validated = $request->validate([
                'tipo_vacuna_id' => 'sometimes|required|exists:tipos_vacuna,id',
                'fecha_aplicacion' => 'sometimes|required|date',
                'numero_dosis' => 'sometimes|required|string|max:50',
                'lote_serie' => 'sometimes|required|string|max:100',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'fecha_proxima_dosis' => 'nullable|date|after:fecha_aplicacion',
                'observaciones' => 'nullable|string|max:500',
                'costo' => 'nullable|numeric|min:0',
                'medio_envio' => 'sometimes|in:email,telegram,whatsapp',
            ]);

            // 🟢 VALIDACIÓN CRUZADA: Si se está cambiando el tipo de vacuna
            if (isset($validated['tipo_vacuna_id']) && $validated['tipo_vacuna_id'] != $vacuna->tipo_vacuna_id) {
                
                // Obtener la mascota asociada
                $mascota = $vacuna->procesoMedico->mascota;
                
                // Obtener el nuevo tipo de vacuna
                $nuevoTipoVacuna = TipoVacuna::findOrFail($validated['tipo_vacuna_id']);
                
                // Ejecutar validación cruzada
                $validacion = $this->validationService->validarMascotaParaTipoVacuna(
                    $mascota, 
                    $nuevoTipoVacuna
                );
                
                // Si la validación falla, retornar error
                if (!$validacion['valido']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La vacuna seleccionada no es válida para esta mascota',
                        'errors' => $validacion['errors'],
                        'detalles' => $validacion['detalles']
                    ], 422); // 422 Unprocessable Entity
                }
                
                Log::info('✅ Validación cruzada superada para actualización de vacuna', [
                    'vacuna_id' => $vacuna->id,
                    'tipo_vacuna_anterior' => $vacuna->tipo_vacuna_id,
                    'tipo_vacuna_nuevo' => $validated['tipo_vacuna_id'],
                    'mascota_id' => $mascota->id
                ]);
            }

            DB::transaction(function () use ($validated, $vacuna) {
                // Actualizar la vacuna
                $vacuna->update([
                    'tipo_vacuna_id' => $validated['tipo_vacuna_id'] ?? $vacuna->tipo_vacuna_id,
                    'numero_dosis' => $validated['numero_dosis'] ?? $vacuna->numero_dosis,
                    'lote_serie' => $validated['lote_serie'] ?? $vacuna->lote_serie,
                    'fecha_proxima_dosis' => $validated['fecha_proxima_dosis'] ?? $vacuna->fecha_proxima_dosis,
                ]);

                // Actualizar el proceso médico asociado
                if ($vacuna->procesoMedico) {
                    $updateData = [];
                    
                    if (isset($validated['centro_veterinario_id'])) {
                        $updateData['centro_veterinario_id'] = $validated['centro_veterinario_id'];
                    }
                    if (isset($validated['fecha_aplicacion'])) {
                        $updateData['fecha_aplicacion'] = $validated['fecha_aplicacion'];
                    }
                    if (isset($validated['observaciones'])) {
                        $updateData['observaciones'] = $validated['observaciones'];
                    }
                    if (isset($validated['costo'])) {
                        $updateData['costo'] = $validated['costo'];
                    }
                    
                    if (!empty($updateData)) {
                        $vacuna->procesoMedico->update($updateData);
                    }
                }
            });

            // Si se solicitó reenvío del certificado
            $mensajeEnvio = '';
            if ($request->has('medio_envio') && $request->medio_envio) {
                try {
                    $mascotaData = $vacuna->procesoMedico->mascota;
                    $vacunaCargada = $vacuna->load(['tipo', 'procesoMedico.centroVeterinario']);
                    
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoVacuna(
                        $vacunaCargada, 
                        $mascotaData, 
                        $request->medio_envio
                    );

                    $mensajeEnvio = ' y certificado reenviado';
                    
                    Log::info('✅ Certificado reenviado después de actualización', [
                        'vacuna_id' => $vacuna->id,
                        'medio_envio' => $request->medio_envio
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error reenviando certificado: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error reenviando certificado después de actualización', [
                        'vacuna_id' => $vacuna->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('✅ Vacuna actualizada exitosamente', [
                'vacuna_id' => $vacuna->id,
                'mascota_id' => $vacuna->procesoMedico->mascota_id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vacuna actualizada exitosamente' . $mensajeEnvio,
                'data' => $vacuna->load(['tipo', 'procesoMedico.centroVeterinario', 'procesoMedico.mascota'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('❌ Error actualizando vacuna', [
                'vacuna_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la vacuna: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para obtener medios de contacto de un usuario
     */
    public function obtenerMediosContacto($usuarioId): JsonResponse
    {
        try {
            $contacto = ContactoUsuario::where('usuario_id', $usuarioId)->first();

            if (!$contacto) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de contacto para este usuario',
                    'data' => []
                ], 404);
            }

            $medios = [];

            // Email
            if ($contacto->email) {
                $medios[] = [
                    'id' => 'email',
                    'nombre' => 'Email',
                    'valor' => $contacto->email,
                    'icon' => 'Mail',
                    'color' => 'text-blue-500'
                ];
            }

            // WhatsApp
            if ($contacto->telefono) {
                $medios[] = [
                    'id' => 'whatsapp',
                    'nombre' => 'WhatsApp',
                    'valor' => $contacto->telefono,
                    'icon' => 'MessageCircle',
                    'color' => 'text-green-500'
                ];
            }

            // Telegram
            if ($contacto->telegram_chat_id) {
                $medios[] = [
                    'id' => 'telegram',
                    'nombre' => 'Telegram',
                    'valor' => 'Chat ID: ' . $contacto->telegram_chat_id,
                    'icon' => 'Send',
                    'color' => 'text-sky-500'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $medios
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo medios de contacto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar medios de contacto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            // Buscar la vacuna
            $vacuna = Vacuna::find($id);
            
            if (!$vacuna) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vacuna no encontrada'
                ], 404);
            }

            // Verificar permisos - solo el veterinario que la creó o admin puede eliminarla
            $user = auth()->user();
            $veterinarioId = $vacuna->procesoMedico->veterinario_id ?? null;
            
            if ($veterinarioId !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para eliminar esta vacuna'
                ], 403);
            }

            // PASAR $user AL CLOSURE CON "use"
            DB::transaction(function () use ($vacuna, $user) {
                // 1. Obtener el proceso médico asociado
                $procesoMedico = $vacuna->procesoMedico;
                
                // 2. Realizar baja lógica en la vacuna
                $vacuna->delete();
                
                // 3. También realizar baja lógica en el proceso médico
                if ($procesoMedico) {
                    $procesoMedico->delete();
                }
                
                Log::info('✅ Vacuna eliminada lógicamente', [
                    'vacuna_id' => $vacuna->id,
                    'mascota_id' => $procesoMedico->mascota_id ?? null,
                    'veterinario_id' => $user->id,
                    'deleted_at' => now()
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Vacuna eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar vacuna', [
                'vacuna_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la vacuna: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Nuevo endpoint para validar antes de mostrar el formulario
     */
    public function validarTipoVacuna(Request $request, $mascotaId, $tipoVacunaId): JsonResponse
    {
        try {
            $mascota = Mascota::findOrFail($mascotaId);
            $tipoVacuna = TipoVacuna::findOrFail($tipoVacunaId);

            $validacion = $this->validationService
                ->validarMascotaParaTipoVacuna($mascota, $tipoVacuna);

            return response()->json([
                'success' => $validacion['valido'],
                'valido' => $validacion['valido'],
                'errors' => $validacion['errors'],
                'detalles' => $validacion['detalles'],
                'tipo_vacuna' => [
                    'id' => $tipoVacuna->id,
                    'nombre' => $tipoVacuna->nombre,
                    'especies' => $tipoVacuna->especies,
                    'edad_minima' => $tipoVacuna->edad_minima,
                    'edad_unidad' => $tipoVacuna->edad_unidad
                ],
                'mascota' => [
                    'id' => $mascota->id,
                    'nombre' => $mascota->nombre,
                    'especie' => $mascota->especie,
                    'edad_formateada' => $mascota->edad_formateada
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en validación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de vacuna válidos para una mascota
     */
    public function tiposVacunaValidos($mascotaId): JsonResponse
    {
        try {
            $mascota = Mascota::findOrFail($mascotaId);
            $tiposVacuna = TipoVacuna::where('activo', true)->get();

            $tiposValidos = [];
            $tiposInvalidos = [];

            foreach ($tiposVacuna as $tipo) {
                $validacion = $this->validationService
                    ->validarMascotaParaTipoVacuna($mascota, $tipo);

                if ($validacion['valido']) {
                    $tiposValidos[] = [
                        'id' => $tipo->id,
                        'nombre' => $tipo->nombre,
                        'enfermedades' => $tipo->enfermedades,
                        'detalles' => $validacion['detalles']
                    ];
                } else {
                    $tiposInvalidos[] = [
                        'id' => $tipo->id,
                        'nombre' => $tipo->nombre,
                        'errors' => $validacion['errors'],
                        'detalles' => $validacion['detalles']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'validos' => $tiposValidos,
                    'invalidos' => $tiposInvalidos,
                    'total_validos' => count($tiposValidos),
                    'total_invalidos' => count($tiposInvalidos)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo tipos válidos: ' . $e->getMessage()
            ], 500);
        }
    }
}