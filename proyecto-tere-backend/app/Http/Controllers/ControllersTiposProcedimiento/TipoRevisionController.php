<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoRevisionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $tiposRevision = TipoRevision::where('activo', true)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposRevision,
                'message' => 'Tipos de revisión obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:tipos_revision,nombre',
                'descripcion' => 'required|string',
                'frecuencia_recomendada' => [
                    'required',
                    Rule::in([
                        'anual', 
                        'semestral', 
                        'trimestral', 
                        'mensual', 
                        'post_procedimiento', 
                        'personalizada'
                    ])
                ],
                'frecuencia_personalizada' => 'nullable|string|max:255|required_if:frecuencia_recomendada,personalizada',
                'areas_revisar' => 'required|array|min:1',
                'areas_revisar.*' => 'string|max:255',
                'otra_area' => 'nullable|string|max:255',
                'indicadores_clave' => 'nullable|string',
                'especie_objetivo' => [
                    'nullable',
                    Rule::in([
                        'canino',
                        'felino', 
                        'ave', 
                        'roedor', 
                        'exotico', 
                        'todos'
                    ])
                ],
                'edad_sugerida' => 'nullable|numeric|min:0|max:100',
                'edad_unidad' => 'nullable|in:semanas,meses,años',
                'recomendaciones_profesionales' => 'nullable|string',
                'riesgos_clinicos' => 'nullable|string',
            ], [
                'nombre.required' => 'El nombre es obligatorio',
                'nombre.unique' => 'Ya existe un tipo de revisión con este nombre',
                'descripcion.required' => 'La descripción es obligatoria',
                'frecuencia_recomendada.required' => 'La frecuencia recomendada es obligatoria',
                'areas_revisar.required' => 'Debe seleccionar al menos un área a revisar',
                'areas_revisar.array' => 'Las áreas a revisar deben ser un array válido',
                'frecuencia_personalizada.required_if' => 'Debe especificar la frecuencia personalizada',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar que si se proporciona edad_sugerida, también se proporcione edad_unidad y viceversa
            if (($request->filled('edad_sugerida') && !$request->filled('edad_unidad')) || 
                (!$request->filled('edad_sugerida') && $request->filled('edad_unidad'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar tanto la edad sugerida como la unidad, o ninguno de los dos',
                    'errors' => [
                        'edad_sugerida' => ['La edad sugerida y la unidad deben proporcionarse juntas'],
                        'edad_unidad' => ['La edad sugerida y la unidad deben proporcionarse juntas']
                    ]
                ], 422);
            }

             // OBTENER EL ID DEL VETERINARIO AUTENTICADO
            $user = auth()->user();
            $validated['veterinario_id'] = $user->userable->id; 

            $tipoRevision = TipoRevision::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'frecuencia_recomendada' => $request->frecuencia_recomendada,
                'frecuencia_personalizada' => $request->frecuencia_personalizada,
                'areas_revisar' => $request->areas_revisar,
                'otra_area' => $request->otra_area,
                'indicadores_clave' => $request->indicadores_clave,
                'especie_objetivo' => $request->especie_objetivo ?? 'todos',
                'edad_sugerida' => $request->edad_sugerida,
                'edad_unidad' => $request->edad_unidad,
                'recomendaciones_profesionales' => $request->recomendaciones_profesionales,
                'riesgos_clinicos' => $request->riesgos_clinicos,
                'veterinario_id' => $validated['veterinario_id'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $tipoRevision,
                'message' => 'Tipo de revisión registrado correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el tipo de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoRevision $tipoRevision): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $tipoRevision,
                'message' => 'Tipo de revisión obtenido correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoRevision $tipoRevision): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tipos_revision')->ignore($tipoRevision->id)
                ],
                'descripcion' => 'required|string',
                'frecuencia_recomendada' => [
                    'required',
                    Rule::in([
                        'anual', 
                        'semestral', 
                        'trimestral', 
                        'mensual', 
                        'post_procedimiento', 
                        'personalizada'
                    ])
                ],
                'frecuencia_personalizada' => 'nullable|string|max:255|required_if:frecuencia_recomendada,personalizada',
                'areas_revisar' => 'required|array|min:1',
                'areas_revisar.*' => 'string|max:255',
                'otra_area' => 'nullable|string|max:255',
                'indicadores_clave' => 'nullable|string',
                'especie_objetivo' => [
                    'nullable',
                    Rule::in([
                        'canino',
                        'felino', 
                        'ave', 
                        'roedor', 
                        'exotico', 
                        'todos'
                    ])
                ],
                'edad_sugerida' => 'nullable|numeric|min:0|max:100',
                'edad_unidad' => 'nullable|in:semanas,meses,años',
                'recomendaciones_profesionales' => 'nullable|string',
                'riesgos_clinicos' => 'nullable|string',
                'activo' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar edad_sugerida y edad_unidad
            if (($request->filled('edad_sugerida') && !$request->filled('edad_unidad')) || 
                (!$request->filled('edad_sugerida') && $request->filled('edad_unidad'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar tanto la edad sugerida como la unidad, o ninguno de los dos'
                ], 422);
            }

            $tipoRevision->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'frecuencia_recomendada' => $request->frecuencia_recomendada,
                'frecuencia_personalizada' => $request->frecuencia_personalizada,
                'areas_revisar' => $request->areas_revisar,
                'otra_area' => $request->otra_area,
                'indicadores_clave' => $request->indicadores_clave,
                'especie_objetivo' => $request->especie_objetivo ?? 'todos',
                'edad_sugerida' => $request->edad_sugerida,
                'edad_unidad' => $request->edad_unidad,
                'recomendaciones_profesionales' => $request->recomendaciones_profesionales,
                'riesgos_clinicos' => $request->riesgos_clinicos,
                'activo' => $request->has('activo') ? $request->activo : $tipoRevision->activo,
            ]);

            return response()->json([
                'success' => true,
                'data' => $tipoRevision,
                'message' => 'Tipo de revisión actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoRevision $tipoRevision): JsonResponse
    {
        try {
            // En lugar de soft delete, marcamos como inactivo
            $tipoRevision->update([
                'activo' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de revisión desactivado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el tipo de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar un tipo de revisión eliminado
     */
    public function restore($id): JsonResponse
    {
        try {
            $tipoRevision = TipoRevision::withTrashed()->findOrFail($id);
            $tipoRevision->restore();

            return response()->json([
                'success' => true,
                'data' => $tipoRevision,
                'message' => 'Tipo de revisión restaurado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el tipo de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de revisión por especie
     */
    public function porEspecie($especie): JsonResponse
    {
        try {
            $tiposRevision = TipoRevision::where('activo', true)
                ->porEspecie($especie)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposRevision,
                'message' => 'Tipos de revisión filtrados por especie correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar tipos de revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener áreas predefinidas para revisión
     */
    public function areasPredefinidas(): JsonResponse
    {
        try {
            $areas = TipoRevision::getAreasPredefinidas();

            return response()->json([
                'success' => true,
                'data' => $areas,
                'message' => 'Áreas predefinidas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas predefinidas: ' . $e->getMessage()
            ], 500);
        }
    }
}