<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoAlergia;
use App\Models\Veterinario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoAlergiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Cambiar la consulta para incluir solo activos y no eliminados
            $query = TipoAlergia::with('veterinario.user')
                        ->where('activo', true)
                        ->whereNull('deleted_at'); // Asegurar que no incluya soft deleted

            // Filtros opcionales (mantener igual)
            if ($request->has('categoria') && $request->categoria !== 'todas') {
                $query->where('categoria', $request->categoria);
            }

            if ($request->has('nivel_riesgo') && $request->nivel_riesgo !== 'todos') {
                $query->where('nivel_riesgo', $request->nivel_riesgo);
            }

            if ($request->has('especie') && $request->especie !== 'todas') {
                $query->porEspecie($request->especie);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function($q) use ($request) {
                    $q->where('nombre', 'like', '%' . $request->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $request->search . '%')
                    ->orWhere('reaccion_comun', 'like', '%' . $request->search . '%');
                });
            }

            $tiposAlergia = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $tiposAlergia,
                'total' => $tiposAlergia->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar que el usuario autenticado es un veterinario
            $user = Auth::user();
            if (!$user || $user->userable_type !== 'App\\Models\\Veterinario') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los veterinarios pueden crear tipos de alergia.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:tipos_alergia,nombre',
                'descripcion' => 'required|string|min:10',
                'categoria' => ['required', Rule::in(['medicamento', 'alimento', 'ambiental', 'contacto', 'otra'])],
                'categoria_otro' => 'required_if:categoria,otra|string|max:255|nullable',
                'reaccion_comun' => 'required|string|max:255',
                'nivel_riesgo' => ['required', Rule::in(['leve', 'moderado', 'grave', 'muy_grave'])],
                'areas_afectadas' => 'required|array|min:1',
                'areas_afectadas.*' => 'string|max:255',
                'otra_area' => 'string|max:255|nullable',
                'tratamiento_recomendado' => 'string|nullable',
                'recomendaciones_clinicas' => 'string|nullable',
                'especies' => 'required|array|min:1',
                'especies.*' => ['string', Rule::in(['canino', 'felino', 'equino', 'bovino', 'ave', 'pez', 'otro', 'todos'])],
                'desencadenante' => 'string|max:255|nullable',
                'conducta_recomendada' => 'string|max:500|nullable',
                'observaciones_adicionales' => 'string|max:500|nullable',
            ], [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'Ya existe un tipo de alergia con este nombre.',
                'descripcion.required' => 'La descripción clínica es obligatoria.',
                'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
                'categoria.required' => 'La categoría es obligatoria.',
                'reaccion_comun.required' => 'La reacción común es obligatoria.',
                'nivel_riesgo.required' => 'El nivel de riesgo es obligatorio.',
                'areas_afectadas.required' => 'Debe seleccionar al menos un área afectada.',
                'areas_afectadas.min' => 'Debe seleccionar al menos un área afectada.',
                'especie.required' => 'La especie afectada es obligatoria.',
                'conducta_recomendada.max' => 'La conducta recomendada no puede exceder los 500 caracteres.',
                'observaciones_adicionales.max' => 'Las observaciones adicionales no pueden exceder los 500 caracteres.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener el veterinario_id del usuario autenticado
            $veterinario = Veterinario::find($user->userable_id);
            if (!$veterinario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el perfil de veterinario.'
                ], 404);
            }

            // Crear el tipo de alergia
            $tipoAlergia = TipoAlergia::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'categoria' => $request->categoria,
                'categoria_otro' => $request->categoria_otro,
                'reaccion_comun' => $request->reaccion_comun,
                'nivel_riesgo' => $request->nivel_riesgo,
                'areas_afectadas' => $request->areas_afectadas,
                'otra_area' => $request->otra_area,
                'tratamiento_recomendado' => $request->tratamiento_recomendado,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'especies' => $request->especies,
                'desencadenante' => $request->desencadenante,
                'conducta_recomendada' => $request->conducta_recomendada,
                'observaciones_adicionales' => $request->observaciones_adicionales,
                'veterinario_id' => $veterinario->id,
                'activo' => true
            ]);

            // Cargar relaciones para la respuesta
            $tipoAlergia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de alergia registrado correctamente.',
                'data' => $tipoAlergia
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el tipo de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoAlergia $tipoAlergia): JsonResponse
    {
        try {
            $tipoAlergia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'data' => $tipoAlergia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tipo de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoAlergia $tipoAlergia): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verificar permisos: solo el veterinario creador puede modificar
            if ($tipoAlergia->veterinario_id !== $user->userable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para modificar este tipo de alergia.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => ['required', 'string', 'max:255', Rule::unique('tipos_alergia')->ignore($tipoAlergia->id)],
                'descripcion' => 'required|string|min:10',
                'categoria' => ['required', Rule::in(['medicamento', 'alimento', 'ambiental', 'contacto', 'otra'])],
                'categoria_otro' => 'required_if:categoria,otra|string|max:255|nullable',
                'reaccion_comun' => 'required|string|max:255',
                'nivel_riesgo' => ['required', Rule::in(['leve', 'moderado', 'grave', 'muy_grave'])],
                'areas_afectadas' => 'required|array|min:1',
                'areas_afectadas.*' => 'string|max:255',
                'otra_area' => 'string|max:255|nullable',
                'tratamiento_recomendado' => 'string|nullable',
                'recomendaciones_clinicas' => 'string|nullable',
                'especies' => 'required|array|min:1',
                'especies.*' => ['string', Rule::in(['canino', 'felino', 'equino', 'bovino', 'ave', 'pez', 'otro', 'todos'])],
                'desencadenante' => 'string|max:255|nullable',
                'conducta_recomendada' => 'string|max:500|nullable',
                'observaciones_adicionales' => 'string|max:500|nullable',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tipoAlergia->update($request->all());

            $tipoAlergia->load('veterinario.user');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de alergia actualizado correctamente.',
                'data' => $tipoAlergia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoAlergia $tipoAlergia): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verificar permisos: solo el veterinario creador puede eliminar
            if ($tipoAlergia->veterinario_id !== $user->userable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar este tipo de alergia.'
                ], 403);
            }

            // Baja lógica: actualizar activo a false Y hacer soft delete
            $tipoAlergia->update(['activo' => false]);
            $tipoAlergia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de alergia eliminado correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id): JsonResponse
    {
        try {
            $tipoAlergia = TipoAlergia::withTrashed()->findOrFail($id);
            
            $user = Auth::user();
            if ($tipoAlergia->veterinario_id !== $user->userable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para restaurar este tipo de alergia.'
                ], 403);
            }

            $tipoAlergia->restore();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de alergia restaurado correctamente.',
                'data' => $tipoAlergia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el tipo de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener categorías predefinidas
     */
    public function categoriasPredefinidas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TipoAlergia::getCategorias()
        ]);
    }

    /**
     * Obtener áreas predefinidas
     */
    public function areasPredefinidas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TipoAlergia::getAreasPredefinidas()
        ]);
    }

    /**
     * Obtener niveles de riesgo predefinidos
     */
    public function nivelesRiesgoPredefinidos(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TipoAlergia::getNivelesRiesgo()
        ]);
    }

    /**
     * Obtener especies predefinidas
     */
    public function especiesPredefinidas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'canino' => 'Canino',
                'felino' => 'Felino',
                'ave' => 'Ave',
                'roedor' => 'Roedor',
                'exotico' => 'Exótico',
                'todos' => 'Todos'
            ]
        ]);
    }

    /**
     * Obtener tipos de alergia por especie
     */
    public function porEspecie($especie): JsonResponse
    {
        try {
            $tiposAlergia = TipoAlergia::porEspecie($especie)
                            ->where('activo', true)
                            ->orderBy('nombre')
                            ->get();

            return response()->json([
                'success' => true,
                'data' => $tiposAlergia,
                'total' => $tiposAlergia->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de alergia por especie: ' . $e->getMessage()
            ], 500);
        }
    }
}