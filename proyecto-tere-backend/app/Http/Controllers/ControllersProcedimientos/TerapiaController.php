<?php
// app/Http/Controllers/ControllersProcedimientos/TerapiaController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Terapia;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoTerapia;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
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
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del modelo Terapia
        $validated = $request->validate([
            'tipo_terapia_id' => 'required|exists:tipos_terapia,id',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:' . implode(',', Terapia::getFrecuenciasPermitidas()),
            'duracion_tratamiento' => 'required|string|max:100',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'evolucion' => 'nullable|in:' . implode(',', Terapia::getEvolucionesPermitidas()),
            'recomendaciones_tutor' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'costo' => 'nullable|numeric|min:0',
            'archivos.*' => 'nullable|file|max:10240', // 10MB máximo por archivo
            'medio_envio' => 'nullable|string|in:email,whatsapp,telegram' // AÑADIR ESTO
        ]);

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

            // Obtener las terapias con sus relaciones
            $terapias = Terapia::with([
                'tipoTerapia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->map(function($terapia) {
                return [
                    'id' => $terapia->id,
                    'tipo' => $terapia->tipoTerapia->nombre ?? 'Terapia no especificada',
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
                'procesoMedico.mascota',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])->find($id);

            if (!$terapia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terapia no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $terapia
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener terapia:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
    public function update(Request $request, Terapia $terapia)
    {
        $validated = $request->validate([
            'tipo_terapia_id' => 'required|exists:tipos_terapia,id',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:' . implode(',', Terapia::getFrecuenciasPermitidas()),
            'duracion_tratamiento' => 'required|string|max:100',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'evolucion' => 'nullable|in:' . implode(',', Terapia::getEvolucionesPermitidas()),
            'recomendaciones_tutor' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinario,id',
            'costo' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $terapia) {
                // 1. Actualizar la terapia
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

                // 2. Actualizar el proceso médico asociado
                $terapia->procesoMedico->update([
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'fecha_aplicacion' => $validated['fecha_inicio'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Terapia actualizada exitosamente',
                'data' => $terapia->fresh()->load(['tipoTerapia', 'procesoMedico.centroVeterinario'])
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar terapia', [
                'terapia_id' => $terapia->id,
                'error' => $e->getMessage()
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
    public function destroy(Terapia $terapia): JsonResponse
    {
        try {
            DB::transaction(function () use ($terapia) {
                // Eliminar primero el proceso médico asociado
                $terapia->procesoMedico()->delete();
                // Luego eliminar la terapia
                $terapia->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Terapia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar terapia', [
                'terapia_id' => $terapia->id,
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