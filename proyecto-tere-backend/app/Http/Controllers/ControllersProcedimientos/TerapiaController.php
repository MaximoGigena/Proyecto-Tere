<?php
// app/Http/Controllers/ControllersProcedimientos/TerapiaController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Terapia;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoTerapia;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use App\Http\Requests\StoreTerapiaRequest;
use App\Http\Requests\UpdateTerapiaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;

class TerapiaController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nueva terapia
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposTerapia = TipoTerapia::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('terapias.create', compact('mascota', 'tiposTerapia', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nueva terapia
     */
    public function store(StoreTerapiaRequest $request, $mascotaId)
    {
        // La validación ya se hizo en el Request
        $validated = $request->validated();

        try {
            $terapiaCreada = null;
            $mascotaData = null;

            // Capturar el medio de envío antes de la transacción
            $medioEnvio = $request->input('medio_envio', null);

            DB::transaction(function () use ($validated, $mascotaId, $request, &$terapiaCreada, &$mascotaData) {
                // 1. Crear el registro específico de Terapia
                $terapia = Terapia::create([
                    'tipo_terapia_id' => $validated['tipo_terapia_id'],
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'frecuencia' => $validated['frecuencia'],
                    'duracion_tratamiento' => $validated['duracion_tratamiento'],
                    'fecha_fin' => $validated['fecha_fin'] ?? null,
                    'evolucion' => $validated['evolucion'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? null,
                    'observaciones' => $validated['observaciones'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'clinico',
                    'fecha_aplicacion' => $validated['fecha_inicio'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Asociar la terapia con el proceso médico
                $terapia->procesoMedico()->save($procesoMedico);

                // 4. Manejar archivos adjuntos si existen
                if ($request->hasFile('archivos')) {
                    foreach ($request->file('archivos') as $index => $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            $path = $archivo->store("terapias/{$terapia->id}", 'public');
                            
                            Log::info('Archivo subido para terapia', [
                                'terapia_id' => $terapia->id,
                                'archivo' => $archivo->getClientOriginalName(),
                                'path' => $path
                            ]);
                        }
                    }
                }

                // 5. Cargar relaciones para posible uso futuro
                $terapiaCreada = $terapia->load([
                    'tipoTerapia',
                    'procesoMedico.centroVeterinario'
                ]);

                // 6. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // ENVIAR CERTIFICADO SI SE SELECCIONÓ MEDIO DE ENVÍO
            if ($medioEnvio && $terapiaCreada && $mascotaData) {
                try {
                    $this->envioDocumentosService->enviarCertificadoTerapia(
                        $terapiaCreada,
                        $mascotaData,
                        $medioEnvio
                    );
                    
                    Log::info('✅ Certificado de terapia enviado exitosamente', [
                        'terapia_id' => $terapiaCreada->id,
                        'medio' => $medioEnvio
                    ]);
                    
                    $mensaje = 'Terapia registrada exitosamente y certificado enviado';
                } catch (\Exception $e) {
                    Log::error('⚠️ Error enviando certificado de terapia: ' . $e->getMessage());
                    $mensaje = 'Terapia registrada exitosamente (error al enviar certificado: ' . $e->getMessage() . ')';
                }
            } else {
                $mensaje = 'Terapia registrada exitosamente';
            }

            // DEVUELVE JSON PARA API
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'terapia' => $terapiaCreada,
                    'mascota' => $mascotaData
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar terapia', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // DEVUELVE JSON ERROR PARA API
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las terapias de una mascota
     */
    // En el método index del controlador:
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

            // Obtener las terapias SIN ELIMINADAS
            $terapias = Terapia::with([
                'tipoTerapia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->whereNull('deleted_at') // AÑADIR: excluir eliminadas
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->map(function($terapia) {
                return [
                    'id' => $terapia->id,
                    'tipo_terapia' => $terapia->tipoTerapia->nombre ?? 'Terapia no especificada',
                    'tipo_terapia_id' => $terapia->tipo_terapia_id,
                    'fecha_inicio' => $terapia->fecha_inicio,
                    'fecha_fin' => $terapia->fecha_fin,
                    'frecuencia' => $terapia->frecuencia,
                    'duracion_tratamiento' => $terapia->duracion_tratamiento,
                    'evolucion' => $terapia->evolucion,
                    'recomendaciones_tutor' => $terapia->recomendaciones_tutor,
                    'observaciones' => $terapia->procesoMedico->observaciones ?? null,
                    'centro_veterinario' => $terapia->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $terapia->procesoMedico->veterinario->name ?? 'No especificado',
                    'costo' => $terapia->procesoMedico->costo ?? null,
                    'esta_activa' => $terapia->estaActiva(),
                    'eliminada' => !is_null($terapia->deleted_at), // AÑADIR: estado de eliminación
                    'created_at' => $terapia->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $terapias
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener terapias: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las terapias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una terapia específica
     */
    public function show($id): JsonResponse
    {
        try {
            $terapia = Terapia::with([
                'tipoTerapia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procesoMedico.mascota',
            ])->find($id);

            if (!$terapia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terapia no encontrada'
                ], 404);
            }

            // Formatear la respuesta de manera consistente
            $formattedData = [
                'id' => $terapia->id,
                'tipo_terapia_id' => $terapia->tipo_terapia_id,
                'fecha_inicio' => $terapia->fecha_inicio,
                'frecuencia' => $terapia->frecuencia,
                'duracion_tratamiento' => $terapia->duracion_tratamiento,
                'fecha_fin' => $terapia->fecha_fin,
                'evolucion' => $terapia->evolucion,
                'recomendaciones_tutor' => $terapia->recomendaciones_tutor,
                'observaciones' => $terapia->observaciones,
                'centro_veterinario_id' => $terapia->procesoMedico->centro_veterinario_id ?? null,
                'costo' => $terapia->procesoMedico->costo ?? null,
                'medio_envio' => $terapia->procesoMedico->medio_envio ?? null, // Agregar si existe
                'proceso_medico' => $terapia->procesoMedico ? [
                    'id' => $terapia->procesoMedico->id,
                    'mascota_id' => $terapia->procesoMedico->mascota_id,
                    'veterinario_id' => $terapia->procesoMedico->veterinario_id,
                    'centro_veterinario_id' => $terapia->procesoMedico->centro_veterinario_id,
                    'observaciones' => $terapia->procesoMedico->observaciones,
                    'costo' => $terapia->procesoMedico->costo,
                ] : null,
                'tipo_terapia' => $terapia->tipoTerapia,
                'created_at' => $terapia->created_at,
                'updated_at' => $terapia->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener terapia:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener la terapia'
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar terapia
     */
    public function edit(Terapia $terapia)
    {
        $terapia->load('procesoMedico');
        $tiposTerapia = TipoTerapia::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('terapias.edit', compact('terapia', 'tiposTerapia', 'centrosVeterinarios'));
    }

    /**
     * Actualizar terapia
     */
    public function update(UpdateTerapiaRequest $request, $id)
    { 
        Log::info('📥 Datos recibidos en update terapia:', [
            'terapia_id' => $id,
            'all_data' => $request->all()
        ]);
        
        // Buscar la terapia (el Request ya validó)
        $terapia = Terapia::with('procesoMedico')->find($id);
        
        if (!$terapia) {
            return response()->json([
                'success' => false,
                'message' => 'Terapia no encontrada'
            ], 404);
        }

        // Los datos ya están validados por UpdateTerapiaRequest
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $terapia) {
                // Actualizar la terapia
                $terapia->update([
                    'tipo_terapia_id' => $validated['tipo_terapia_id'],
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'frecuencia' => $validated['frecuencia'],
                    'duracion_tratamiento' => $validated['duracion_tratamiento'],
                    'fecha_fin' => $validated['fecha_fin'] ?? null,
                    'evolucion' => $validated['evolucion'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? null,
                    'observaciones' => $validated['observaciones'] ?? null,
                ]);

                // Actualizar el proceso médico asociado
                if ($terapia->procesoMedico) {
                    $terapia->procesoMedico->update([
                        'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                        'fecha_aplicacion' => $validated['fecha_inicio'],
                        'observaciones' => $validated['observaciones'] ?? null,
                        'costo' => $validated['costo'] ?? null,
                    ]);
                }
            });

            // Cargar relaciones actualizadas
            $terapia->load(['tipoTerapia', 'procesoMedico.centroVeterinario']);

            // ENVIAR CERTIFICADO SI SE SELECCIONÓ MEDIO DE ENVÍO (opcional, similar al store)
            if ($request->input('medio_envio')) {
                $mascotaData = Mascota::with('usuario')->find($terapia->procesoMedico->mascota_id);
                try {
                    $this->envioDocumentosService->enviarCertificadoTerapia(
                        $terapia,
                        $mascotaData,
                        $request->input('medio_envio')
                    );
                    Log::info('✅ Certificado de terapia reenviado en actualización');
                } catch (\Exception $e) {
                    Log::warning('⚠️ Error reenviando certificado en actualización: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Terapia actualizada exitosamente',
                'data' => $terapia
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar terapia', [
                'terapia_id' => $terapia->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar terapia
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Buscar la terapia con su proceso médico
            $terapia = Terapia::with('procesoMedico')->find($id);
            
            if (!$terapia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terapia no encontrada'
                ], 404);
            }

            // Verificar si ya está eliminada
            if ($terapia->deleted_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta terapia ya fue eliminada anteriormente'
                ], 400);
            }

            DB::transaction(function () use ($terapia) {
                // 1. Marcar el proceso médico como eliminado (si también quieres baja lógica)
                if ($terapia->procesoMedico) {
                    // Si ProcesoMedico también usa SoftDeletes
                    if (method_exists($terapia->procesoMedico, 'delete')) {
                        $terapia->procesoMedico->delete(); // Esto hará baja lógica si tiene SoftDeletes
                    } else {
                        // Si no tiene SoftDeletes, podrías marcar un campo como 'eliminado'
                        $terapia->procesoMedico->update(['activo' => false]);
                    }
                }

                // 2. Baja lógica de la terapia
                $terapia->delete(); // Esto marcará deleted_at automáticamente

                // 3. Opcional: Registrar quién eliminó y cuándo
                $terapia->update([
                    'deleted_by' => Auth::id(), // Si quieres registrar quién lo eliminó
                ]);
            });

            Log::info('✅ Terapia marcada como eliminada (baja lógica)', [
                'terapia_id' => $terapia->id,
                'deleted_by' => Auth::id(),
                'deleted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terapia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar terapia', [
                'terapia_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener terapias activas de una mascota
     */
    public function activas($mascotaId): JsonResponse
    {
        try {
            $terapias = Terapia::with(['tipoTerapia', 'procesoMedico.centroVeterinario'])
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->activas()
                ->orderBy('fecha_inicio', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $terapias
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener terapias activas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las terapias activas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener medios de contacto del tutor (similar al de vacunas)
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
}