<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoCirugia;
use App\Models\Veterinario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TipoCirugiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Iniciando creación de tipo de cirugía', ['data' => $request->except('equipamiento')]);
            // Cambiar la consulta para incluir solo activos y no eliminados
            $query = TipoCirugia::with('veterinario.user')
                        ->where('activo', true)
                        ->whereNull('deleted_at'); // Asegurar que no incluya soft deleted

            // Filtros opcionales
            if ($request->has('especie') && $request->especie !== 'todas') {
                $query->porEspecie($request->especie);
            }

            if ($request->has('frecuencia') && $request->frecuencia !== 'todas') {
                $query->porFrecuencia($request->frecuencia);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function($q) use ($request) {
                    $q->where('nombre', 'like', '%' . $request->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $request->search . '%')
                      ->orWhere('riesgos', 'like', '%' . $request->search . '%')
                      ->orWhere('recomendaciones_preoperatorias', 'like', '%' . $request->search . '%');
                });
            }

            $tiposCirugia = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $tiposCirugia,
                'total' => $tiposCirugia->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Obtener el veterinario autenticado
            $user = auth()->user();
            $validated['veterinario_id'] = $user->userable->id;
            // Obtener el veterinario desde la relación userable
            $veterinario = $user->userable;

            // Verificar que el usuario sea un veterinario
            if (!$veterinario || !$veterinario instanceof Veterinario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los veterinarios pueden crear tipos de cirugía'
                ], 403);
            }

            // Validar los datos
            $validator = Validator::make($request->all(), TipoCirugia::rules(), TipoCirugia::messages());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Validación pasada exitosamente');

            // Procesar equipamiento si se proporciona como string
            $equipamiento = $request->equipamiento;
            if (is_string($equipamiento)) {
                $equipamiento = array_map('trim', explode(';', $equipamiento));
                $equipamiento = array_filter($equipamiento);
            }

            // Crear el tipo de cirugía
            $tipoCirugia = TipoCirugia::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'especie' => $request->especie,
                'frecuencia' => $request->frecuencia,
                'duracion' => $request->duracion,
                'duracion_unidad' => $request->duracion_unidad,
                'riesgos' => $request->riesgos,
                'recomendaciones_preoperatorias' => $request->recomendaciones_preoperatorias,
                'recomendaciones_postoperatorias' => $request->recomendaciones_postoperatorias,
                'requerimientos_anestesia' => $request->requerimientos_anestesia,
                'equipamiento' => $equipamiento,
                'observaciones' => $request->observaciones,
                'veterinario_id' => $veterinario->id,
                'activo' => true
            ]);

            Log::info('Tipo de cirugía creado exitosamente', ['id' => $tipoCirugia->id]);

            // Cargar relaciones para la respuesta
            $tipoCirugia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cirugía creado exitosamente',
                'data' => $tipoCirugia
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error crítico al crear tipo de cirugía', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoCirugia $tipoCirugia): JsonResponse
    {
        try {
            // Cargar relaciones
            $tipoCirugia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'data' => $tipoCirugia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoCirugia $tipoCirugia): JsonResponse
    {
        try {
            Log::info('Iniciando actualización de tipo de cirugía', [
                'id' => $tipoCirugia->id,
                'data' => $request->all()
            ]);

            // Obtener el veterinario autenticado
            $user = auth()->user();
            
            // Verificar que el usuario tenga relación userable y sea veterinario
            if (!$user->userable || !$user->userable instanceof Veterinario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los veterinarios pueden actualizar tipos de cirugía'
                ], 403);
            }

            $veterinario = $user->userable;

            // Validar los datos (excluyendo el registro actual para la validación unique)
            $rules = TipoCirugia::rules();
            $rules['nombre'] = 'required|string|max:255|unique:tipos_cirugia,nombre,' . $tipoCirugia->id;

            $validator = Validator::make($request->all(), $rules, TipoCirugia::messages());

            if ($validator->fails()) {
                Log::error('Error de validación en actualización', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Validación pasada exitosamente');

            // Procesar equipamiento
            $equipamiento = $request->equipamiento;
            if (is_string($equipamiento)) {
                $equipamiento = array_map('trim', explode(';', $equipamiento));
                $equipamiento = array_filter($equipamiento);
            } elseif (is_array($equipamiento)) {
                $equipamiento = array_filter($equipamiento);
            } else {
                $equipamiento = null;
            }

            // Actualizar el tipo de cirugía
            $tipoCirugia->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'especie' => $request->especie,
                'frecuencia' => $request->frecuencia,
                'duracion' => $request->duracion,
                'duracion_unidad' => $request->duracion_unidad,
                'riesgos' => $request->riesgos,
                'recomendaciones_preoperatorias' => $request->recomendaciones_preoperatorias,
                'recomendaciones_postoperatorias' => $request->recomendaciones_postoperatorias,
                'requerimientos_anestesia' => $request->requerimientos_anestesia,
                'equipamiento' => $equipamiento,
                'observaciones' => $request->observaciones,
            ]);

            Log::info('Tipo de cirugía actualizado exitosamente', ['id' => $tipoCirugia->id]);

            // Cargar relaciones actualizadas
            $tipoCirugia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cirugía actualizado exitosamente',
                'data' => $tipoCirugia
            ]);

        } catch (\Exception $e) {
            Log::error('Error crítico al actualizar tipo de cirugía', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoCirugia $tipoCirugia): JsonResponse
    {
        try {
            Log::info('Iniciando eliminación de tipo de cirugía', ['id' => $tipoCirugia->id]);

            // Obtener el veterinario autenticado
            $user = auth()->user();
            
            // Verificar que el usuario tenga relación userable y sea veterinario
            if (!$user->userable || !$user->userable instanceof Veterinario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los veterinarios pueden eliminar tipos de cirugía'
                ], 403);
            }

            $veterinario = $user->userable;

            // Soft delete
            $tipoCirugia->delete();

            Log::info('Tipo de cirugía eliminado exitosamente', ['id' => $tipoCirugia->id]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cirugía eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error crítico al eliminar tipo de cirugía', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id): JsonResponse
    {
        try {
            // Verificar que el usuario es veterinario
            $user = Auth::user();
            $veterinario = Veterinario::where('user_id', $user->id)->first();

            if (!$veterinario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los veterinarios pueden restaurar tipos de cirugía'
                ], 403);
            }

            $tipoCirugia = TipoCirugia::withTrashed()->findOrFail($id);
            $tipoCirugia->restore();

            // Cargar relaciones
            $tipoCirugia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cirugía restaurado exitosamente',
                'data' => $tipoCirugia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el tipo de cirugía: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener especies predefinidas
     */
    public function especiesPredefinidas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'canino',
                'felino', 
                'ave',
                'roedor',
                'exotico',
                'todos'
            ]
        ]);
    }

    /**
     * Obtener frecuencias predefinidas
     */
    public function frecuenciasPredefinidas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unica',
                'potencial_repetible',
                'multiple'
            ]
        ]);
    }

    /**
     * Obtener unidades de duración predefinidas
     */
    public function unidadesDuracionPredefinidas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'minutos',
                'horas'
            ]
        ]);
    }

    /**
     * Filtrar tipos de cirugía por especie
     */
    public function porEspecie($especie): JsonResponse
    {
        try {
            $tiposCirugia = TipoCirugia::with('veterinario.user')
                ->where('activo', true)
                ->whereNull('deleted_at')
                ->porEspecie($especie)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposCirugia,
                'total' => $tiposCirugia->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de cirugía por especie: ' . $e->getMessage()
            ], 500);
        }
    }
}