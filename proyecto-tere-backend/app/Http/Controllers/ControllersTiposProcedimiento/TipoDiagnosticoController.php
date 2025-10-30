<?php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Models\TiposProcedimientos\TipoDiagnostico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TipoDiagnosticoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposDiagnostico = TipoDiagnostico::with('veterinario')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tiposDiagnostico
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Datos recibidos en store:', $request->all());
            Log::info('User ID:', ['user_id' => Auth::id()]);

            DB::beginTransaction();

            // Validar los datos
            $validator = Validator::make($request->all(), TipoDiagnostico::rules(), TipoDiagnostico::messages());

            if ($validator->fails()) {
                Log::error('Errores de validación:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // OBTENER EL VETERINARIO_ID CORRECTO
            $user = Auth::user();
            $veterinarioId = null;

            // Si el usuario tiene relación userable (polimórfica)
            if ($user->userable && $user->userable_type === 'App\\Models\\Veterinario') {
                $veterinarioId = $user->userable->id;
                Log::info('Veterinario ID obtenido de relación userable:', ['veterinario_id' => $veterinarioId]);
            } 
            // Si el usuario es directamente un veterinario
            else if (method_exists($user, 'veterinario')) {
                $veterinarioId = $user->id;
                Log::info('Usuario es veterinario directo:', ['veterinario_id' => $veterinarioId]);
            }
            // Si no hay relación, usar null (temporalmente)
            else {
                Log::warning('No se pudo determinar veterinario_id, usando null');
                $veterinarioId = null;
            }

            // Preparar datos para crear el diagnóstico
            $datosDiagnostico = [
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'clasificacion' => $request->clasificacion,
                'clasificacion_otro' => $request->clasificacion_otro,
                'especie' => $request->especie,
                'evolucion' => $request->evolucion,
                'criterios_diagnosticos' => $request->criterios_diagnosticos,
                'tratamiento_sugerido' => $request->tratamiento_sugerido,
                'riesgos_complicaciones' => $request->riesgos_complicaciones,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'observaciones' => $request->observaciones,
                'veterinario_id' => $veterinarioId, // Usar el ID correcto
                'activo' => true
            ];

            Log::info('Datos preparados para crear:', $datosDiagnostico);

            // Crear el tipo de diagnóstico
            $tipoDiagnostico = TipoDiagnostico::create($datosDiagnostico);
            Log::info('Tipo diagnóstico creado:', ['id' => $tipoDiagnostico->id]);

            // Cargar la relación del veterinario para la respuesta
            $tipoDiagnostico->load('veterinario');

            DB::commit();

            Log::info('Transacción completada exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Tipo de diagnóstico creado exitosamente',
                'data' => $tipoDiagnostico
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error completo en store: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de diagnóstico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $tipoDiagnostico = TipoDiagnostico::with('veterinario')
                ->where('activo', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $tipoDiagnostico
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de diagnóstico no encontrado'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $tipoDiagnostico = TipoDiagnostico::where('activo', true)->findOrFail($id);

            // Reglas de validación para actualización (ignorar unique para el mismo registro)
            $rules = TipoDiagnostico::rules();
            $rules['nombre'] = 'required|string|max:255|unique:tipos_diagnostico,nombre,' . $id;

            $validator = Validator::make($request->all(), $rules, TipoDiagnostico::messages());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar el diagnóstico
            $tipoDiagnostico->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'clasificacion' => $request->clasificacion,
                'clasificacion_otro' => $request->clasificacion_otro,
                'especie' => $request->especie,
                'evolucion' => $request->evolucion,
                'criterios_diagnosticos' => $request->criterios_diagnosticos,
                'tratamiento_sugerido' => $request->tratamiento_sugerido,
                'riesgos_complicaciones' => $request->riesgos_complicaciones,
                'recomendaciones_clinicas' => $request->recomendaciones_clinicas,
                'observaciones' => $request->observaciones,
            ]);

            $tipoDiagnostico->load('veterinario');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de diagnóstico actualizado exitosamente',
                'data' => $tipoDiagnostico
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de diagnóstico no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de diagnóstico',
                'error' => $e->getMessage()
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

            $tipoDiagnostico = TipoDiagnostico::where('activo', true)->findOrFail($id);

            // Soft delete
            $tipoDiagnostico->update(['activo' => false]);
            $tipoDiagnostico->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de diagnóstico eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de diagnóstico no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de diagnóstico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtrar tipos de diagnóstico por criterios
     */
    public function filtrar(Request $request)
    {
        try {
            $query = TipoDiagnostico::with('veterinario')->where('activo', true);

            // Filtro por clasificación
            if ($request->has('clasificacion') && $request->clasificacion) {
                $query->porClasificacion($request->clasificacion);
            }

            // Filtro por especie
            if ($request->has('especie') && $request->especie) {
                $query->porEspecie($request->especie);
            }

            // Filtro por evolución
            if ($request->has('evolucion') && $request->evolucion) {
                $query->porEvolucion($request->evolucion);
            }

            // Búsqueda por término
            if ($request->has('busqueda') && $request->busqueda) {
                $query->buscar($request->busqueda);
            }

            $resultados = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $resultados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar tipos de diagnóstico',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de tipos de diagnóstico
     */
    public function estadisticas()
    {
        try {
            $total = TipoDiagnostico::where('activo', true)->count();
            
            $porClasificacion = TipoDiagnostico::where('activo', true)
                ->select('clasificacion', DB::raw('count(*) as total'))
                ->groupBy('clasificacion')
                ->get();

            $porEspecie = TipoDiagnostico::where('activo', true)
                ->select('especie', DB::raw('count(*) as total'))
                ->groupBy('especie')
                ->get();

            $porEvolucion = TipoDiagnostico::where('activo', true)
                ->select('evolucion', DB::raw('count(*) as total'))
                ->groupBy('evolucion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'por_clasificacion' => $porClasificacion,
                    'por_especie' => $porEspecie,
                    'por_evolucion' => $porEvolucion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}