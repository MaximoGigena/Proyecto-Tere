<?php
// app/Http/Controllers/ControllersProcedimientos/CirugiaController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\Cirugia;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\ArchivoCirugia;
use App\Models\TiposProcedimientos\TipoCirugia;
use App\Models\CentroVeterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class CirugiaController extends Controller
{
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
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del modelo
        $validated = $request->validate([
            'tipo_cirugia_id' => 'required|exists:tipos_cirugia,id',
            'fecha_cirugia' => 'required|date',
            'resultado' => 'required|in:' . implode(',', Cirugia::getResultadosPermitidos()),
            'estado_actual' => 'required|in:' . implode(',', Cirugia::getEstadosPermitidos()),
            'diagnostico_causa' => 'required|string|max:255',
            
            // Campos opcionales
            'fecha_control_estimada' => 'nullable|date|after_or_equal:fecha_cirugia',
            'descripcion_procedimiento' => 'nullable|string|max:1000',
            'medicacion_postquirurgica' => 'nullable|string|max:500',
            'recomendaciones_tutor' => 'nullable|string|max:500',
            
            // Campos para proceso médico
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            
            // Archivos adjuntos
            'archivos' => 'nullable|array',
            'archivos.*' => 'file|max:10240', // Máximo 10MB por archivo
        ]);

        try {
            $cirugiaCreada = null;

            DB::transaction(function () use ($validated, $mascotaId, &$cirugiaCreada) {
                // 1. Crear el registro específico de Cirugía
                $cirugia = Cirugia::create([
                    'tipo_cirugia_id' => $validated['tipo_cirugia_id'],
                    'fecha_cirugia' => $validated['fecha_cirugia'],
                    'resultado' => $validated['resultado'],
                    'estado_actual' => $validated['estado_actual'],
                    'diagnostico_causa' => $validated['diagnostico_causa'],
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
                    'categoria' => 'clinico', // Siempre clínico para cirugías
                    'fecha_aplicacion' => $validated['fecha_cirugia'], // Usamos la misma fecha de cirugía
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                    'procesable_type' => Cirugia::class,
                    'procesable_id' => $cirugia->id,
                ]);

                // 3. Asociar la cirugía con el proceso médico
                $procesoMedico->save();
                
                // 4. Guardar archivos adjuntos si existen
                if (isset($validated['archivos'])) {
                    $this->guardarArchivos($cirugia, $validated['archivos']);
                }
                
                // 5. Cargar relaciones para la respuesta
                $cirugiaCreada = $cirugia->load([
                    'tipoCirugia',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario',
                    'archivos'
                ]);
            });
            
            /** @var \App\Models\Cirugia $cirugiaCreada */
            Log::info('✅ Cirugía registrada exitosamente', [
                'cirugia_id' => $cirugiaCreada->id,
                'mascota_id' => $mascotaId,
                'veterinario_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cirugía registrada exitosamente',
                'data' => $cirugiaCreada
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error al registrar cirugía', [
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
     * Guardar archivos adjuntos
     */
    private function guardarArchivos($cirugia, $archivos)
    {
        foreach ($archivos as $archivo) {
            $path = $archivo->store('cirugias/' . $cirugia->id, 'public');
            
            $cirugia->archivos()->create([
                'nombre_original' => $archivo->getClientOriginalName(),
                'ruta' => $path,
                'tipo' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
            ]);
        }
    }

    /**
     * Obtener todas las cirugías de una mascota
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
                'archivos'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha_cirugia', 'desc')
            ->get()
            ->map(function($cirugia) {
                return [
                    'id' => $cirugia->id,
                    'tipo_cirugia' => $cirugia->tipoCirugia->nombre ?? 'No especificado',
                    'fecha_cirugia' => $cirugia->fecha_cirugia,
                    'resultado' => $cirugia->resultado,
                    'estado_actual' => $cirugia->estado_actual,
                    'diagnostico_causa' => $cirugia->diagnostico_causa,
                    'fecha_control_estimada' => $cirugia->fecha_control_estimada,
                    'descripcion_procedimiento' => $cirugia->descripcion_procedimiento,
                    'centro_veterinario' => $cirugia->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $cirugia->procesoMedico->veterinario->name ?? 'No especificado',
                    'observaciones' => $cirugia->procesoMedico->observaciones,
                    'costo' => $cirugia->procesoMedico->costo,
                    'archivos' => $cirugia->archivos->map(function($archivo) {
                        return [
                            'id' => $archivo->id,
                            'nombre' => $archivo->nombre_original,
                            'ruta' => Storage::url($archivo->ruta),
                            'tipo' => $archivo->tipo,
                            'tamano' => $this->formatBytes($archivo->tamano)
                        ];
                    }),
                    'created_at' => $cirugia->created_at
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
                'message' => 'Error al cargar las cirugías: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una cirugía específica
     */
    public function show($id): JsonResponse
    {
        try {
            $cirugia = Cirugia::with([
                'tipoCirugia',
                'procesoMedico.mascota',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'archivos'
            ])->find($id);

            if (!$cirugia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cirugía no encontrada'
                ], 404);
            }

            // Verificar permisos (opcional)
            $user = auth()->user();
            $mascota = $cirugia->procesoMedico->mascota;
            
            if ($mascota->usuario_id !== $user->id) {
                if (!isset($user->es_veterinario) || !$user->es_veterinario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autorizado para ver esta cirugía'
                    ], 403);
                }
            }

            $data = [
                'id' => $cirugia->id,
                'tipo_cirugia' => $cirugia->tipoCirugia,
                'fecha_cirugia' => $cirugia->fecha_cirugia,
                'resultado' => $cirugia->resultado,
                'estado_actual' => $cirugia->estado_actual,
                'diagnostico_causa' => $cirugia->diagnostico_causa,
                'fecha_control_estimada' => $cirugia->fecha_control_estimada,
                'descripcion_procedimiento' => $cirugia->descripcion_procedimiento,
                'medicacion_postquirurgica' => $cirugia->medicacion_postquirurgica,
                'recomendaciones_tutor' => $cirugia->recomendaciones_tutor,
                'mascota' => $mascota,
                'centro_veterinario' => $cirugia->procesoMedico->centroVeterinario,
                'veterinario' => $cirugia->procesoMedico->veterinario,
                'observaciones' => $cirugia->procesoMedico->observaciones,
                'costo' => $cirugia->procesoMedico->costo,
                'archivos' => $cirugia->archivos->map(function($archivo) {
                    return [
                        'id' => $archivo->id,
                        'nombre' => $archivo->nombre_original,
                        'ruta' => Storage::url($archivo->ruta),
                        'tipo' => $archivo->tipo,
                        'tamano' => $this->formatBytes($archivo->tamano),
                        'descargar_url' => route('cirugias.archivo.download', $archivo->id)
                    ];
                }),
                'created_at' => $cirugia->created_at,
                'updated_at' => $cirugia->updated_at
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener cirugía:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener la cirugía'
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar cirugía
     */
    public function edit(Cirugia $cirugia)
    {
        $cirugia->load('procesoMedico');
        $tiposCirugia = TipoCirugia::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('cirugias.edit', compact('cirugia', 'tiposCirugia', 'centrosVeterinarios'));
    }

    /**
     * Actualizar cirugía
     */
    public function update(Request $request, Cirugia $cirugia)
    {
        $validated = $request->validate([
            'tipo_cirugia_id' => 'required|exists:tipos_cirugia,id',
            'fecha_cirugia' => 'required|date',
            'resultado' => 'required|in:' . implode(',', Cirugia::getResultadosPermitidos()),
            'estado_actual' => 'required|in:' . implode(',', Cirugia::getEstadosPermitidos()),
            'diagnostico_causa' => 'required|string|max:255',
            'fecha_control_estimada' => 'nullable|date|after_or_equal:fecha_cirugia',
            'descripcion_procedimiento' => 'nullable|string|max:1000',
            'medicacion_postquirurgica' => 'nullable|string|max:500',
            'recomendaciones_tutor' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'nuevos_archivos' => 'nullable|array',
            'nuevos_archivos.*' => 'file|max:10240',
            'archivos_a_eliminar' => 'nullable|array',
            'archivos_a_eliminar.*' => 'exists:archivos_cirugias,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $cirugia) {
                // 1. Actualizar la cirugía
                $cirugia->update([
                    'tipo_cirugia_id' => $validated['tipo_cirugia_id'],
                    'fecha_cirugia' => $validated['fecha_cirugia'],
                    'resultado' => $validated['resultado'],
                    'estado_actual' => $validated['estado_actual'],
                    'diagnostico_causa' => $validated['diagnostico_causa'],
                    'fecha_control_estimada' => $validated['fecha_control_estimada'] ?? null,
                    'descripcion_procedimiento' => $validated['descripcion_procedimiento'] ?? null,
                    'medicacion_postquirurgica' => $validated['medicacion_postquirurgica'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? null,
                ]);

                // 2. Actualizar el proceso médico asociado
                $cirugia->procesoMedico->update([
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'fecha_aplicacion' => $validated['fecha_cirugia'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Eliminar archivos si se especifican
                if (isset($validated['archivos_a_eliminar'])) {
                    foreach ($validated['archivos_a_eliminar'] as $archivoId) {
                        $archivo = $cirugia->archivos()->find($archivoId);
                        if ($archivo) {
                            Storage::delete($archivo->ruta);
                            $archivo->delete();
                        }
                    }
                }

                // 4. Agregar nuevos archivos
                if (isset($validated['nuevos_archivos'])) {
                    $this->guardarArchivos($cirugia, $validated['nuevos_archivos']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Cirugía actualizada exitosamente',
                'data' => $cirugia->load(['procesoMedico', 'tipoCirugia', 'archivos'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar cirugía:', [
                'cirugia_id' => $cirugia->id,
                'error' => $e->getMessage()
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
    public function destroy(Cirugia $cirugia)
    {
        try {
            DB::transaction(function () use ($cirugia) {
                // Eliminar archivos primero
                foreach ($cirugia->archivos as $archivo) {
                    Storage::delete($archivo->ruta);
                    $archivo->delete();
                }

                // Eliminar proceso médico asociado
                $cirugia->procesoMedico->delete();

                // Finalmente eliminar la cirugía
                $cirugia->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Cirugía eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar cirugía:', [
                'cirugia_id' => $cirugia->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo($archivoId)
    {
        try {
            $archivo = ArchivoCirugia::findOrFail($archivoId);
            
            // Verificar permisos
            $user = auth()->user();
            $cirugia = $archivo->cirugia;
            $mascota = $cirugia->procesoMedico->mascota;
            
            if ($mascota->usuario_id !== $user->id) {
                if (!isset($user->es_veterinario) || !$user->es_veterinario) {
                    abort(403, 'No autorizado');
                }
            }

            return Storage::download($archivo->ruta, $archivo->nombre_original);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo de cirugía:', [
                'archivo_id' => $archivoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo'
            ], 500);
        }
    }

    /**
     * Obtener tipos de cirugía para select
     */
    public function obtenerTiposCirugia(): JsonResponse
    {
        try {
            $tipos = TipoCirugia::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'descripcion']);

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener tipos de cirugía: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar tipos de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper para formatear bytes
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}