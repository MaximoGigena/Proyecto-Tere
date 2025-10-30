<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoPaliativo;
use App\Models\Veterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TipoPaliativoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = TipoPaliativo::with('veterinario');

            // Filtros
            if ($request->has('especie') && $request->especie) {
                $query->porEspecie($request->especie);
            }

            if ($request->has('objetivo') && $request->objetivo) {
                $query->porObjetivo($request->objetivo);
            }

            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            // Búsqueda
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('objetivo_terapeutico', 'like', "%{$search}%")
                      ->orWhere('objetivo_otro', 'like', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortField = $request->get('sort_field', 'nombre');
            $sortDirection = $request->get('sort_direction', 'asc');
            
            $allowedSortFields = ['nombre', 'especie', 'objetivo_terapeutico', 'frecuencia_valor', 'created_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            $procedimientos = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $procedimientos,
                'message' => 'Procedimientos paliativos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los procedimientos paliativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Obtener el veterinario autenticado
             $user = auth()->user();
             $validated['veterinario_id'] = $user->userable->id;
             $veterinario = Veterinario::find($validated['veterinario_id']);

            if (!$validated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene un perfil de veterinario asociado'
                ], 403);
            }

            // Validar los datos
            $validatedData = $request->validate(
                TipoPaliativo::rules(),
                TipoPaliativo::messages()
            );

            // Preparar datos para crear el procedimiento
            $procedimientoData = [
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'],
                'especie' => $validatedData['especie'],
                'objetivo_terapeutico' => $validatedData['objetivo_terapeutico'],
                'objetivo_otro' => $validatedData['objetivo_otro'] ?? null,
                'frecuencia_valor' => $validatedData['frecuencia_valor'],
                'frecuencia_unidad' => $validatedData['frecuencia_unidad'],
                'indicaciones_clinicas' => $validatedData['indicaciones_clinicas'],
                'contraindicaciones' => $validatedData['contraindicaciones'] ?? null,
                'riesgos_efectos_secundarios' => $validatedData['riesgos_efectos_secundarios'] ?? null,
                'recursos_necesarios' => $validatedData['recursos_necesarios'] ?? [],
                'recomendaciones_clinicas' => $validatedData['recomendaciones_clinicas'] ?? null,
                'observaciones' => $validatedData['observaciones'] ?? null,
                'veterinario_id' => $veterinario->id,
                'activo' => true,
            ];

            // Crear el procedimiento paliativo
            $procedimiento = TipoPaliativo::create($procedimientoData);

            DB::commit();

            // Cargar la relación del veterinario para la respuesta
            $procedimiento->load('veterinario');

            return response()->json([
                'success' => true,
                'data' => $procedimiento,
                'message' => 'Procedimiento paliativo creado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $procedimiento = TipoPaliativo::with('veterinario')->find($id);

            if (!$procedimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procedimiento paliativo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $procedimiento,
                'message' => 'Procedimiento paliativo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $procedimiento = TipoPaliativo::find($id);

            if (!$procedimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procedimiento paliativo no encontrado'
                ], 404);
            }

            // Reglas de validación para actualización (excluyendo el nombre único para este registro)
            $rules = array_merge(
                array_diff_key(TipoPaliativo::rules(), ['nombre' => '']),
                ['nombre' => 'required|string|max:255|unique:tipos_paliativo,nombre,' . $id]
            );

            $validatedData = $request->validate($rules, TipoPaliativo::messages());

            // Actualizar el procedimiento
            $procedimiento->update($validatedData);

            DB::commit();

            // Recargar las relaciones
            $procedimiento->load('veterinario');

            return response()->json([
                'success' => true,
                'data' => $procedimiento,
                'message' => 'Procedimiento paliativo actualizado exitosamente'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $procedimiento = TipoPaliativo::find($id);

            if (!$procedimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procedimiento paliativo no encontrado'
                ], 404);
            }

            // Marcar como inactivo antes de eliminar

            $procedimiento->update([
                'activo' => false
            ]);

            // Usar soft delete
            $procedimiento->delete();
            

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Procedimiento paliativo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore($id)
    {
        try {
            DB::beginTransaction();

            $procedimiento = TipoPaliativo::withTrashed()->find($id);

            if (!$procedimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procedimiento paliativo no encontrado'
                ], 404);
            }

            if (!$procedimiento->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El procedimiento paliativo no está eliminado'
                ], 400);
            }

            $procedimiento->restore();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $procedimiento,
                'message' => 'Procedimiento paliativo restaurado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el procedimiento paliativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener opciones predefinidas para el formulario
     */
    public function opcionesPredefinidas()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'especies' => [
                    ['value' => 'canino', 'label' => 'Canino'],
                    ['value' => 'felino', 'label' => 'Felino'],
                    ['value' => 'ave', 'label' => 'Ave'],
                    ['value' => 'roedor', 'label' => 'Roedor'],
                    ['value' => 'exotico', 'label' => 'Exótico'],
                    ['value' => 'todos', 'label' => 'Todos'],
                ],
                'objetivos_terapeuticos' => [
                    ['value' => 'alivio_dolor', 'label' => 'Alivio del dolor'],
                    ['value' => 'mejora_movilidad', 'label' => 'Mejora de movilidad'],
                    ['value' => 'soporte_respiratorio', 'label' => 'Soporte respiratorio'],
                    ['value' => 'soporte_nutricional', 'label' => 'Soporte nutricional'],
                    ['value' => 'acompañamiento', 'label' => 'Acompañamiento final'],
                    ['value' => 'otro', 'label' => 'Otro'],
                ],
                'unidades_frecuencia' => [
                    ['value' => 'horas', 'label' => 'Horas'],
                    ['value' => 'dias', 'label' => 'Días'],
                    ['value' => 'semanas', 'label' => 'Semanas'],
                    ['value' => 'meses', 'label' => 'Meses'],
                    ['value' => 'sesiones', 'label' => 'Sesiones'],
                ]
            ],
            'message' => 'Opciones predefinidas obtenidas exitosamente'
        ]);
    }

    /**
     * Filtrar procedimientos por especie
     */
    public function porEspecie($especie)
    {
        try {
            $procedimientos = TipoPaliativo::porEspecie($especie)
                ->where('activo', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $procedimientos,
                'message' => 'Procedimientos paliativos filtrados por especie'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar procedimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/desactivar procedimiento
     */
    public function toggleActivo($id)
    {
        try {
            DB::beginTransaction();

            $procedimiento = TipoPaliativo::find($id);

            if (!$procedimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procedimiento paliativo no encontrado'
                ], 404);
            }

            $procedimiento->activo = !$procedimiento->activo;
            $procedimiento->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $procedimiento,
                'message' => 'Estado del procedimiento actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del procedimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}