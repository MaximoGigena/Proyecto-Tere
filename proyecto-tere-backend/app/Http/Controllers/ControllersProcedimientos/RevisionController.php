<?php
// app/Http/Controllers/ControllersProcedimientos/RevisionController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Revision;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoRevision;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;

class RevisionController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nueva revisión
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposRevision = TipoRevision::where('activo', true)->get();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('revisiones.create', compact('mascota', 'tiposRevision', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nueva revisión
     */
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del caso de uso
        $validated = $request->validate([
            'tipo_revision_id' => 'required|exists:tipos_revision,id',
            'fecha_revision' => 'required|date',
            'nivel_urgencia' => 'required|in:rutinaria,preventiva,urgencia,emergencia',
            'motivo_consulta' => 'nullable|string|max:500',
            'diagnostico' => 'nullable|string|max:500',
            'fecha_proxima_revision' => 'nullable|date|after:fecha_revision',
            'indicaciones_medicas' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
            'archivos.*' => 'nullable|file|max:10240', // Máximo 10MB por archivo
        ]);

        try {
            $revisionCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$revisionCreada, &$mascotaData) {
                // 1. Crear el registro específico de Revisión
                $revision = Revision::create([
                    'tipo_revision_id' => $validated['tipo_revision_id'],
                    'fecha_revision' => $validated['fecha_revision'],
                    'nivel_urgencia' => $validated['nivel_urgencia'],
                    'motivo_consulta' => $validated['motivo_consulta'] ?? null,
                    'diagnostico' => $validated['diagnostico'] ?? null,
                    'fecha_proxima_revision' => $validated['fecha_proxima_revision'] ?? null,
                    'indicaciones_medicas' => $validated['indicaciones_medicas'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => $this->determinarCategoria($validated['nivel_urgencia']),
                    'fecha_aplicacion' => $validated['fecha_revision'], // Usamos fecha_revision como fecha_aplicacion
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Asociar la revisión con el proceso médico
                $revision->procesoMedico()->save($procesoMedico);
                
                // 4. Cargar relaciones para el informe
                $revisionCreada = $revision->load([
                    'tipoRevision',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.mascota'
                ]);
                
                // 5. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 6. Manejar archivos adjuntos (después de la transacción exitosa)
            if ($revisionCreada && $request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $archivo) {
                    if ($archivo->isValid()) {
                        $path = $archivo->store('revisiones/' . $revisionCreada->id, 'public');
                        
                        // Aquí puedes crear un modelo para los archivos adjuntos si lo necesitas
                        // Por ejemplo: $revisionCreada->archivos()->create(['ruta' => $path, 'nombre' => $archivo->getClientOriginalName()]);
                    }
                }
            }

            // 7. Enviar informe PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($revisionCreada && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarInformeRevision(
                        $revisionCreada, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' e informe enviado';
                    
                    Log::info('✅ Informe de revisión enviado exitosamente', [
                        'revision_id' => $revisionCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando informe: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando informe de revisión', [
                        'revision_id' => $revisionCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // DEVUELVE JSON PARA API
            return response()->json([
                'success' => true,
                'message' => 'Revisión registrada exitosamente' . $mensajeEnvio,
                'data' => [
                    'revision' => $revisionCreada,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar revisión', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // DEVUELVE JSON ERROR PARA API
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determinar categoría basada en el nivel de urgencia
     */
   private function determinarCategoria($nivelUrgencia)
    {
        // Siempre es 'preventivo' para ProcesoMedico
        return 'preventivo';
    }

    /**
     * Mostrar todas las revisiones de una mascota
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

            // Obtener las revisiones con sus relaciones
            $revisiones = Revision::with([
                'tipoRevision',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($revision) {
                return [
                    'id' => $revision->id,
                    'tipo_revision_id' => $revision->tipo_revision_id,
                    'tipo_revision_nombre' => $revision->tipoRevision->nombre ?? 'No especificado',
                    'fecha_revision' => $revision->fecha_revision,
                    'nivel_urgencia' => $revision->nivel_urgencia,
                    'motivo_consulta' => $revision->motivo_consulta,
                    'diagnostico' => $revision->diagnostico,
                    'fecha_proxima_revision' => $revision->fecha_proxima_revision,
                    'indicaciones_medicas' => $revision->indicaciones_medicas,
                    'recomendaciones_tutor' => $revision->recomendaciones_tutor,
                    'centro_veterinario_id' => $revision->procesoMedico->centro_veterinario_id ?? null,
                    'centro_veterinario_nombre' => $revision->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $revision->procesoMedico->veterinario->name ?? 'No especificado',
                    'costo' => $revision->procesoMedico->costo,
                    'observaciones' => $revision->procesoMedico->observaciones,
                    'created_at' => $revision->created_at,
                    'updated_at' => $revision->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $revisiones
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener revisiones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las revisiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una revisión específica
     */
    public function show($mascotaId, $revisionId): JsonResponse
    {
        try {
            $revision = Revision::with([
                'tipoRevision',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procesoMedico.mascota'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->findOrFail($revisionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'revision' => $revision,
                    'archivos' => $revision->archivos ?? [] // Si tienes relación de archivos
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener revisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Revisión no encontrada'
            ], 404);
        }
    }

    /**
     * Actualizar una revisión existente
     */
    public function update(Request $request, $mascotaId, $revisionId): JsonResponse
    {
        try {
            $request->validate([
                'tipo_revision_id' => 'sometimes|exists:tipos_revision,id',
                'fecha_revision' => 'sometimes|date',
                'nivel_urgencia' => 'sometimes|in:rutinaria,preventiva,urgencia,emergencia',
                'motivo_consulta' => 'nullable|string|max:500',
                'diagnostico' => 'nullable|string|max:500',
                'fecha_proxima_revision' => 'nullable|date|after:fecha_revision',
                'indicaciones_medicas' => 'nullable|string',
                'recomendaciones_tutor' => 'nullable|string',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'observaciones' => 'nullable|string|max:500',
                'costo' => 'nullable|numeric|min:0',
            ]);

            $revision = Revision::with('procesoMedico')
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->findOrFail($revisionId);

            DB::transaction(function () use ($request, $revision) {
                // 1. Actualizar la revisión
                $revision->update([
                    'tipo_revision_id' => $request->tipo_revision_id ?? $revision->tipo_revision_id,
                    'fecha_revision' => $request->fecha_revision ?? $revision->fecha_revision,
                    'nivel_urgencia' => $request->nivel_urgencia ?? $revision->nivel_urgencia,
                    'motivo_consulta' => $request->motivo_consulta ?? $revision->motivo_consulta,
                    'diagnostico' => $request->diagnostico ?? $revision->diagnostico,
                    'fecha_proxima_revision' => $request->fecha_proxima_revision ?? $revision->fecha_proxima_revision,
                    'indicaciones_medicas' => $request->indicaciones_medicas ?? $revision->indicaciones_medicas,
                    'recomendaciones_tutor' => $request->recomendaciones_tutor ?? $revision->recomendaciones_tutor,
                ]);

                // 2. Actualizar el proceso médico asociado
                if ($revision->procesoMedico) {
                    $revision->procesoMedico->update([
                        'centro_veterinario_id' => $request->centro_veterinario_id ?? $revision->procesoMedico->centro_veterinario_id,
                        'fecha_aplicacion' => $request->fecha_revision ?? $revision->procesoMedico->fecha_aplicacion,
                        'observaciones' => $request->observaciones ?? $revision->procesoMedico->observaciones,
                        'costo' => $request->costo ?? $revision->procesoMedico->costo,
                        'categoria' => 'preventivo', // ← SIEMPRE 'preventivo'
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Revisión actualizada exitosamente',
                'data' => $revision->load(['tipoRevision', 'procesoMedico.centroVeterinario'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar revisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una revisión
     */
    public function destroy($mascotaId, $revisionId): JsonResponse
    {
        try {
            $revision = Revision::with('procesoMedico')
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->findOrFail($revisionId);

            DB::transaction(function () use ($revision) {
                // Eliminar el proceso médico primero (depende de la relación)
                if ($revision->procesoMedico) {
                    $revision->procesoMedico->delete();
                }
                
                // Luego eliminar la revisión
                $revision->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Revisión eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar revisión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar revisión
     */
    public function edit($mascotaId, $revisionId)
    {
        $revision = Revision::with('procesoMedico')
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->findOrFail($revisionId);

        $tiposRevision = TipoRevision::where('activo', true)->get();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('revisiones.edit', compact('revision', 'tiposRevision', 'centrosVeterinarios'));
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
}