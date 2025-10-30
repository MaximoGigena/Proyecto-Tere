<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoDesparasitacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class TipoDesparasitacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $tiposDesparasitacion = TipoDesparasitacion::with('veterinario')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposDesparasitacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de desparasitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar los datos
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:tipos_desparasitacion,nombre',
                'parasitos' => 'required|array|min:1',
                'parasitos.*' => 'string|max:100',
                'otros_parasitos' => 'nullable|string|max:255|required_if:parasitos,otros',
                'via_administracion' => 'required|in:oral,topica,inyectable,otra',
                'especies' => 'required|array|min:1',
                'especies.*' => 'string|in:canino,felino,ave,roedor,exotico',
                'edad_minima' => 'required|numeric|min:0|max:999.99',
                'edad_unidad' => 'required|in:semanas,meses',
                'frecuencia' => 'required|integer|min:1',
                'frecuencia_unidad' => 'required|in:dias,semanas,meses',
                'recomendaciones' => 'nullable|string',
                'riesgos' => 'nullable|string',
                'dosis_recomendada' => 'nullable|string|max:500'
            ], [
                'nombre.required' => 'El nombre del desparasitante es obligatorio',
                'nombre.unique' => 'Ya existe un tipo de desparasitación con este nombre',
                'parasitos.required' => 'Debe seleccionar al menos un parásito',
                'parasitos.array' => 'Los parásitos deben ser un array',
                'especies.required' => 'Debe seleccionar al menos una especie',
                'especies.array' => 'Las especies deben ser un array',
                'edad_minima.required' => 'La edad mínima es obligatoria',
                'frecuencia.required' => 'La frecuencia es obligatoria',
                'via_administracion.required' => 'La vía de administración es obligatoria',
                'otros_parasitos.required_if' => 'Debe especificar los otros parásitos cuando selecciona "otros"'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el veterinario autenticado
            $user = auth()->user();
            $veterinarioId = $user->userable->id;

            // Crear el tipo de desparasitación
            $tipoDesparasitacion = TipoDesparasitacion::create([
                'nombre' => $request->nombre,
                'parasitos' => $request->parasitos,
                'otros_parasitos' => $request->otros_parasitos,
                'via_administracion' => $request->via_administracion,
                'especies' => $request->especies,
                'edad_minima' => $request->edad_minima,
                'edad_unidad' => $request->edad_unidad,
                'frecuencia' => $request->frecuencia,
                'frecuencia_unidad' => $request->frecuencia_unidad,
                'recomendaciones' => $request->recomendaciones,
                'riesgos' => $request->riesgos,
                'dosis_recomendada' => $request->dosis_recomendada,
                'veterinario_id' => $veterinarioId
            ]);

            // Cargar la relación del veterinario para la respuesta
            $tipoDesparasitacion->load('veterinario');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de desparasitación registrado correctamente',
                'data' => $tipoDesparasitacion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el tipo de desparasitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoDesparasitacion $tipoDesparasitacion): JsonResponse
    {
        try {
            $tipoDesparasitacion->load('veterinario');

            return response()->json([
                'success' => true,
                'data' => $tipoDesparasitacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de desparasitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoDesparasitacion $tipoDesparasitacion): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:tipos_desparasitacion,nombre,' . $tipoDesparasitacion->id,
                'parasitos' => 'required|array|min:1',
                'parasitos.*' => 'string|max:100',
                'otros_parasitos' => 'nullable|string|max:255|required_if:parasitos,otros',
                'via_administracion' => 'required|in:oral,topica,inyectable,otra',
                'especies' => 'required|array|min:1',
                'especies.*' => 'string|in:canino,felino,ave,roedor,exotico',
                'edad_minima' => 'required|numeric|min:0|max:999.99',
                'edad_unidad' => 'required|in:semanas,meses',
                'frecuencia' => 'required|integer|min:1',
                'frecuencia_unidad' => 'required|in:dias,semanas,meses',
                'recomendaciones' => 'nullable|string',
                'riesgos' => 'nullable|string',
                'dosis_recomendada' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tipoDesparasitacion->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Tipo de desparasitación actualizado correctamente',
                'data' => $tipoDesparasitacion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de desparasitación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoDesparasitacion $tipoDesparasitacion): JsonResponse
    {
        try {
            Log::info('🧹 Iniciando baja lógica del tipo de desparasitación', [
                'id' => $tipoDesparasitacion->id,
                'nombre' => $tipoDesparasitacion->nombre,
            ]);

            // Actualiza el estado a inactivo antes del soft delete
            $tipoDesparasitacion->update(['activo' => false]);

            // Aplica soft delete (marca deleted_at)
            $tipoDesparasitacion->delete();

            Log::info('✅ Baja lógica completada exitosamente', [
                'id' => $tipoDesparasitacion->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de desparasitación dado de baja correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error en baja lógica del tipo de desparasitación', [
                'id' => $tipoDesparasitacion->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el tipo de desparasitación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Filtrar tipos de desparasitación por especie
     */
    public function porEspecie($especie): JsonResponse
    {
        try {
            $tiposDesparasitacion = TipoDesparasitacion::porEspecie($especie)
                ->with('veterinario')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposDesparasitacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar por especie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtrar tipos de desparasitación por parásito
     */
    public function porParasito($parasito): JsonResponse
    {
        try {
            $tiposDesparasitacion = TipoDesparasitacion::porParasito($parasito)
                ->with('veterinario')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposDesparasitacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar por parásito',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}