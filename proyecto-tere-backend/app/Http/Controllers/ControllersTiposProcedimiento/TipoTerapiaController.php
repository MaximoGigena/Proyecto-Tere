<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoTerapia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TipoTerapiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $terapias = TipoTerapia::where('activo', true)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $terapias,
                'message' => 'Tipos de terapia obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar los datos de entrada
            $validator = Validator::make($request->all(), TipoTerapia::rules(), TipoTerapia::messages());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el veterinario autenticado
            $user = Auth::user();
            
            if (!$user || $user->userable_type !== 'App\\Models\\Veterinario') {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo los veterinarios pueden crear tipos de terapia.'
                ], 403);
            }

            // Crear el tipo de terapia
            $terapia = TipoTerapia::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'especie' => $request->especie,
                'duracion_valor' => $request->duracion_valor,
                'duracion_unidad' => $request->duracion_unidad,
                'frecuencia' => $request->frecuencia,
                'requisitos' => $request->requisitos,
                'indicaciones_clinicas' => $request->indicaciones_clinicas,
                'contraindicaciones' => $request->contraindicaciones,
                'riesgos_efectos_secundarios' => $request->riesgos_efectos_secundarios,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'observaciones' => $request->observaciones,
                'veterinario_id' => $user->userable_id,
                'activo' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $terapia,
                'message' => 'Tipo de terapia creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoTerapia $tipoTerapia): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $tipoTerapia,
                'message' => 'Tipo de terapia obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoTerapia $tipoTerapia): JsonResponse
    {
        try {
            // Validar los datos de entrada
            $rules = TipoTerapia::rules();
            $rules['nombre'] = 'required|string|max:255|unique:tipos_terapia,nombre,' . $tipoTerapia->id;
            
            $validator = Validator::make($request->all(), $rules, TipoTerapia::messages());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar permisos (solo el veterinario creador puede modificar)
            $user = Auth::user();
            if ($tipoTerapia->veterinario_id !== $user->userable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo el veterinario creador puede modificar este tipo de terapia.'
                ], 403);
            }

            // Actualizar el tipo de terapia
            $tipoTerapia->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'especie' => $request->especie,
                'duracion_valor' => $request->duracion_valor,
                'duracion_unidad' => $request->duracion_unidad,
                'frecuencia' => $request->frecuencia,
                'requisitos' => $request->requisitos,
                'indicaciones_clinicas' => $request->indicaciones_clinicas,
                'contraindicaciones' => $request->contraindicaciones,
                'riesgos_efectos_secundarios' => $request->riesgos_efectos_secundarios,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'observaciones' => $request->observaciones,
            ]);

            return response()->json([
                'success' => true,
                'data' => $tipoTerapia,
                'message' => 'Tipo de terapia actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoTerapia $tipoTerapia): JsonResponse
    {
        try {
            // Verificar permisos (solo el veterinario creador puede eliminar)
            $user = Auth::user();
            if ($tipoTerapia->veterinario_id !== $user->userable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo el veterinario creador puede eliminar este tipo de terapia.'
                ], 403);
            }

            // En lugar de soft delete, marcamos como inactivo
            $tipoTerapia->update([
                'activo' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de terapia marcado como inactivo exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el tipo de terapia como inactivo: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id): JsonResponse
    {
        try {
            $tipoTerapia = TipoTerapia::withTrashed()->findOrFail($id);

            // Verificar permisos
            $user = Auth::user();
            if ($tipoTerapia->veterinario_id !== $user->userable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado. Solo el veterinario creador puede restaurar este tipo de terapia.'
                ], 403);
            }

            $tipoTerapia->restore();

            return response()->json([
                'success' => true,
                'data' => $tipoTerapia,
                'message' => 'Tipo de terapia restaurado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el tipo de terapia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de terapia por especie
     */
    public function porEspecie($especie): JsonResponse
    {
        try {
            $terapias = TipoTerapia::porEspecie($especie)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $terapias,
                'message' => 'Tipos de terapia para la especie obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de terapia por especie: ' . $e->getMessage()
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
                ['value' => 'canino', 'label' => 'Canino'],
                ['value' => 'felino', 'label' => 'Felino'],
                ['value' => 'ave', 'label' => 'Ave'],
                ['value' => 'roedor', 'label' => 'Roedor'],
                ['value' => 'exotico', 'label' => 'Exótico'],
                ['value' => 'todos', 'label' => 'Todos']
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
                ['value' => 'diaria', 'label' => 'Diaria'],
                ['value' => 'semanal', 'label' => 'Semanal'],
                ['value' => 'quincenal', 'label' => 'Quincenal'],
                ['value' => 'mensual', 'label' => 'Mensual'],
                ['value' => 'personalizada', 'label' => 'Personalizada']
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
                ['value' => 'sesiones', 'label' => 'Sesiones'],
                ['value' => 'dias', 'label' => 'Días'],
                ['value' => 'semanas', 'label' => 'Semanas'],
                ['value' => 'meses', 'label' => 'Meses']
            ]
        ]);
    }
}