<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoFarmaco;
use App\Models\Veterinario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoFarmacoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $farmacos = TipoFarmaco::where('activo', true)
                ->whereNull('deleted_at') // Solo registros no eliminados
                ->orderBy('nombre_comercial')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $farmacos,
                'message' => 'Tipos de fármaco obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de fármaco: ' . $e->getMessage()
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
                'nombre_comercial' => 'required|string|max:255',
                'nombre_generico' => 'required|string|max:255',
                'composicion' => 'required|string',
                'categoria' => [
                    'required',
                    Rule::in([
                        'analgesico', 
                        'antibiotico', 
                        'antiparasitario', 
                        'antiinflamatorio', 
                        'antifungico', 
                        'antiviral', 
                        'anestesico', 
                        'otro'
                    ])
                ],
                'categoria_otro' => 'nullable|required_if:categoria,otro|string|max:255',
                'especies' => 'required|array|min:1',
                'especies.*' => 'in:canino,felino,equino,bovino,ave,pez,otro',
                'dosis' => 'required|numeric|min:0.1',
                'unidad' => [
                    'required',
                    Rule::in(['mg', 'ml', 'UI', 'mcg', 'gotas'])
                ],
                'frecuencia_unidad' => [
                    'required',
                    Rule::in(['kg', 'dosis'])
                ],
                'frecuencia' => 'required|string|max:100',
                'via_administracion' => [
                    'required',
                    Rule::in([
                        'oral',
                        'subcutanea', 
                        'intramuscular', 
                        'intravenosa', 
                        'topica', 
                        'oftalmica', 
                        'otica', 
                        'otra'
                    ])
                ],
                'indicaciones_clinicas' => 'required|string',
                'contraindicaciones' => 'nullable|string',
                'interacciones_medicamentosas' => 'nullable|string',
                'reacciones_adversas' => 'nullable|string',
                'fabricante' => 'nullable|string|max:255',
                'recomendaciones_clinicas' => 'nullable|string',
                'observaciones' => 'nullable|string',
            ], [
                'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
                'nombre_generico.required' => 'El nombre genérico es obligatorio.',
                'composicion.required' => 'La composición es obligatoria.',
                'categoria.required' => 'La categoría terapéutica es obligatoria.',
                'categoria_otro.required_if' => 'Debe especificar la categoría cuando selecciona "Otro".',
                'dosis.required' => 'La dosis terapéutica es obligatoria.',
                'dosis.min' => 'La dosis debe ser al menos 0.1.',
                'frecuencia.required' => 'La frecuencia de administración es obligatoria.',
                'via_administracion.required' => 'La vía de administración es obligatoria.',
                'indicaciones_clinicas.required' => 'Las indicaciones clínicas son obligatorias.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar que si la categoría es "otro", se proporcione categoria_otro
            if ($request->categoria === 'otro' && !$request->filled('categoria_otro')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar la categoría cuando selecciona "Otro"',
                    'errors' => [
                        'categoria_otro' => ['Debe especificar la categoría cuando selecciona "Otro"']
                    ]
                ], 422);
            }

            // Obtener el ID del veterinario autenticado
            $user = auth()->user();
            $veterinarioId = $user->userable_type === 'App\\Models\\Veterinario' ? $user->userable->id : null;

            $farmaco = TipoFarmaco::create([
                'nombre_comercial' => $request->nombre_comercial,
                'nombre_generico' => $request->nombre_generico,
                'composicion' => $request->composicion,
                'categoria' => $request->categoria,
                'categoria_otro' => $request->categoria_otro,
                'especies' => $request->especies,
                'dosis' => $request->dosis,
                'unidad' => $request->unidad,
                'frecuencia_unidad' => $request->frecuencia_unidad,
                'frecuencia' => $request->frecuencia,
                'via_administracion' => $request->via_administracion,
                'indicaciones_clinicas' => $request->indicaciones_clinicas,
                'contraindicaciones' => $request->contraindicaciones,
                'interacciones_medicamentosas' => $request->interacciones_medicamentosas,
                'reacciones_adversas' => $request->reacciones_adversas,
                'fabricante' => $request->fabricante,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'observaciones' => $request->observaciones,
                'veterinario_id' => $veterinarioId,
                'activo' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => $farmaco,
                'message' => 'Tipo de fármaco registrado correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el tipo de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoFarmaco $tipoFarmaco): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $tipoFarmaco,
                'message' => 'Tipo de fármaco obtenido correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoFarmaco $tipoFarmaco): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre_comercial' => 'required|string|max:255',
                'nombre_generico' => 'required|string|max:255',
                'composicion' => 'required|string',
                'categoria' => [
                    'required',
                    Rule::in([
                        'analgesico', 
                        'antibiotico', 
                        'antiparasitario', 
                        'antiinflamatorio', 
                        'antifungico', 
                        'antiviral', 
                        'anestesico', 
                        'otro'
                    ])
                ],
                'categoria_otro' => 'nullable|required_if:categoria,otro|string|max:255',
                'especies' => 'required|array|min:1',
                'especies.*' => 'in:canino,felino,equino,bovino,ave,pez,otro',
                'dosis' => 'required|numeric|min:0.1',
                'unidad' => [
                    'required',
                    Rule::in(['mg', 'ml', 'UI', 'mcg', 'gotas'])
                ],
                'frecuencia_unidad' => [
                    'required',
                    Rule::in(['kg', 'dosis'])
                ],
                'frecuencia' => 'required|string|max:100',
                'via_administracion' => [
                    'required',
                    Rule::in([
                        'oral',
                        'subcutanea', 
                        'intramuscular', 
                        'intravenosa', 
                        'topica', 
                        'oftalmica', 
                        'otica', 
                        'otra'
                    ])
                ],
                'indicaciones_clinicas' => 'required|string',
                'contraindicaciones' => 'nullable|string',
                'interacciones_medicamentosas' => 'nullable|string',
                'reacciones_adversas' => 'nullable|string',
                'fabricante' => 'nullable|string|max:255',
                'recomendaciones_clinicas' => 'nullable|string',
                'observaciones' => 'nullable|string',
                'activo' => 'boolean',
            ], [
                'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
                'nombre_generico.required' => 'El nombre genérico es obligatorio.',
                'composicion.required' => 'La composición es obligatoria.',
                'categoria.required' => 'La categoría terapéutica es obligatoria.',
                'categoria_otro.required_if' => 'Debe especificar la categoría cuando selecciona "Otro".',
                'dosis.required' => 'La dosis terapéutica es obligatoria.',
                'dosis.min' => 'La dosis debe ser al menos 0.1.',
                'frecuencia.required' => 'La frecuencia de administración es obligatoria.',
                'via_administracion.required' => 'La vía de administración es obligatoria.',
                'indicaciones_clinicas.required' => 'Las indicaciones clínicas son obligatorias.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar que si la categoría es "otro", se proporcione categoria_otro
            if ($request->categoria === 'otro' && !$request->filled('categoria_otro')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar la categoría cuando selecciona "Otro"',
                    'errors' => [
                        'categoria_otro' => ['Debe especificar la categoría cuando selecciona "Otro"']
                    ]
                ], 422);
            }

            $tipoFarmaco->update([
                'nombre_comercial' => $request->nombre_comercial,
                'nombre_generico' => $request->nombre_generico,
                'composicion' => $request->composicion,
                'categoria' => $request->categoria,
                'categoria_otro' => $request->categoria_otro,
                'especies' => $request->especies,
                'dosis' => $request->dosis,
                'unidad' => $request->unidad,
                'frecuencia_unidad' => $request->frecuencia_unidad,
                'frecuencia' => $request->frecuencia,
                'via_administracion' => $request->via_administracion,
                'indicaciones_clinicas' => $request->indicaciones_clinicas,
                'contraindicaciones' => $request->contraindicaciones,
                'interacciones_medicamentosas' => $request->interacciones_medicamentosas,
                'reacciones_adversas' => $request->reacciones_adversas,
                'fabricante' => $request->fabricante,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'observaciones' => $request->observaciones,
                'activo' => $request->has('activo') ? $request->activo : $tipoFarmaco->activo,
            ]);

            return response()->json([
                'success' => true,
                'data' => $tipoFarmaco,
                'message' => 'Tipo de fármaco actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoFarmaco $tipoFarmaco): JsonResponse
    {
        try {
            // Verificar que el usuario autenticado tiene permisos para eliminar este fármaco
            $user = auth()->user();
            
            // Verificar correctamente si es veterinario
            if (!$user->userable || $user->userable_type !== 'App\\Models\\Veterinario') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los veterinarios pueden eliminar tipos de fármaco'
                ], 403);
            }

            // Si es veterinario, solo puede eliminar sus propios fármacos
            $veterinarioId = $user->userable->id;
            if ($tipoFarmaco->veterinario_id !== $veterinarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes eliminar tus propios tipos de fármaco'
                ], 403);
            }

            // Realizar soft delete (esto NO debería afectar veterinario_id)
            $tipoFarmaco->update([
                'activo' => false
            ]);
            $tipoFarmaco->delete(); // Soft delete

            return response()->json([
                'success' => true,
                'message' => 'Tipo de fármaco eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar un tipo de fármaco eliminado
     */
    public function restore($id): JsonResponse
    {
        try {
            $tipoFarmaco = TipoFarmaco::withTrashed()->findOrFail($id);
            $tipoFarmaco->restore();
            $tipoFarmaco->update(['activo' => true]);

            return response()->json([
                'success' => true,
                'data' => $tipoFarmaco,
                'message' => 'Tipo de fármaco restaurado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el tipo de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de fármaco por especie
     */
    public function porEspecie($especie): JsonResponse
    {
        try {
            $farmacos = TipoFarmaco::where('activo', true)
                ->porEspecie($especie)
                ->orderBy('nombre_comercial')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $farmacos,
                'message' => 'Tipos de fármaco filtrados por especie correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar tipos de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de fármaco por categoría
     */
    public function porCategoria($categoria): JsonResponse
    {
        try {
            $farmacos = TipoFarmaco::where('activo', true)
                ->porCategoria($categoria)
                ->orderBy('nombre_comercial')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $farmacos,
                'message' => 'Tipos de fármaco filtrados por categoría correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar tipos de fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar fármacos por término
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'termino' => 'required|string|min:2'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $farmacos = TipoFarmaco::buscar($request->termino)
                ->where('activo', true)
                ->whereNull('deleted_at') // Solo registros no eliminados
                ->orderBy('nombre_comercial')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $farmacos,
                'message' => 'Búsqueda completada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener opciones predefinidas para formularios
     */
    public function opcionesPredefinidas(): JsonResponse
    {
        try {
            $opciones = [
                'categorias' => [
                    ['value' => 'analgesico', 'label' => 'Analgésico'],
                    ['value' => 'antibiotico', 'label' => 'Antibiótico'],
                    ['value' => 'antiparasitario', 'label' => 'Antiparasitario'],
                    ['value' => 'antiinflamatorio', 'label' => 'Antiinflamatorio'],
                    ['value' => 'antifungico', 'label' => 'Antifúngico'],
                    ['value' => 'antiviral', 'label' => 'Antiviral'],
                    ['value' => 'anestesico', 'label' => 'Anestésico'],
                    ['value' => 'otro', 'label' => 'Otro'],
                ],
                'especies' => [
                    ['value' => 'canino', 'label' => 'Canino'],
                    ['value' => 'felino', 'label' => 'Felino'],
                    ['value' => 'equino', 'label' => 'Equino'],
                    ['value' => 'bovino', 'label' => 'Bovino'],
                    ['value' => 'ave', 'label' => 'Ave'],
                    ['value' => 'pez', 'label' => 'Pez'],
                    ['value' => 'otro', 'label' => 'Otro'],
                ],
                'unidades' => [
                    ['value' => 'mg', 'label' => 'mg'],
                    ['value' => 'ml', 'label' => 'ml'],
                    ['value' => 'UI', 'label' => 'UI'],
                    ['value' => 'mcg', 'label' => 'mcg'],
                    ['value' => 'gotas', 'label' => 'gotas'],
                ],
                'frecuencia_unidades' => [
                    ['value' => 'kg', 'label' => 'por kg'],
                    ['value' => 'dosis', 'label' => 'por dosis'],
                ],
                'vias_administracion' => [
                    ['value' => 'oral', 'label' => 'Oral'],
                    ['value' => 'subcutanea', 'label' => 'Subcutánea'],
                    ['value' => 'intramuscular', 'label' => 'Intramuscular'],
                    ['value' => 'intravenosa', 'label' => 'Intravenosa'],
                    ['value' => 'topica', 'label' => 'Tópica'],
                    ['value' => 'oftalmica', 'label' => 'Oftálmica'],
                    ['value' => 'otica', 'label' => 'Ótica'],
                    ['value' => 'otra', 'label' => 'Otra'],
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $opciones,
                'message' => 'Opciones predefinidas obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones predefinidas: ' . $e->getMessage()
            ], 500);
        }
    }
}