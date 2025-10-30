<?php

namespace App\Http\Controllers;

use App\Models\CentroVeterinario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CentroVeterinarioController extends Controller
{
    /**
     * Registrar un nuevo centro veterinario
     */
    public function registrar(Request $request): JsonResponse
    {
        try {
            // Reglas de validación actualizadas
            $rules = [
                'nombre' => 'required|string|max:255',
                'identificacion' => 'required|string|max:20|unique:centros_veterinarios,identificacion',
                'direccion' => 'required|string|max:500',
                'telefono' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:centros_veterinarios,email',
                'especialidades_medicas' => 'required|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'matricula_establecimiento' => 'nullable|string|max:100',
                'horarios_atencion' => 'nullable|string|max:100',
                'web_redes_sociales' => 'nullable|string|max:255',
                'veterinario_id' => 'nullable|exists:veterinarios,id'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Procesar el logo si se envió
            $logoPath = null;
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $logoPath = $request->file('logo')->store('logos/centros_veterinarios', 'public');
            }

            // Crear el centro veterinario - NOMBRES CORREGIDOS
            $centro = CentroVeterinario::create([
                'nombre' => $request->nombre,
                'identificacion' => $request->identificacion,
                'direccion' => $request->direccion,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'especialidades_medicas' => $request->especialidades_medicas, // Corregido
                'logo_path' => $logoPath,
                'matricula_establecimiento' => $request->matricula_establecimiento, // Corregido
                'horarios_atencion' => $request->horarios_atencion, // Corregido
                'web_redes_sociales' => $request->web_redes_sociales, // Corregido
                'veterinario_id' => $request->veterinario_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Centro veterinario registrado exitosamente.',
                'data' => $centro
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los centros veterinarios
     */
    /**
 * Obtener todos los centros veterinarios
 */
    public function index(Request $request): JsonResponse
    {
        try {
            
            $query = CentroVeterinario::with('veterinario');

            $query = CentroVeterinario::with('veterinario')->whereNull('deleted_at');

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('buscar')) {
                $query->buscar($request->buscar);
            }

            if ($request->has('especialidad')) {
                $query->porEspecialidad($request->especialidad);
            }

            $centros = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $centros
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los centros veterinarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un centro veterinario específico
     */
    public function show($id): JsonResponse
    {
        try {
            $centro = CentroVeterinario::with('veterinario')->find($id);

            if (!$centro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Centro veterinario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $centro
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar un centro veterinario
     */
    public function aprobar(Request $request, $id): JsonResponse
    {
        try {
            $centro = CentroVeterinario::find($id);

            if (!$centro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Centro veterinario no encontrado'
                ], 404);
            }

            $aprobado = $centro->aprobar($request->observaciones);

            if ($aprobado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Centro veterinario aprobado exitosamente',
                    'data' => $centro
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el centro veterinario'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar un centro veterinario
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            $centro = CentroVeterinario::find($id);

            if (!$centro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Centro veterinario no encontrado'
                ], 404);
            }

            $rechazado = $centro->rechazar($request->observaciones);

            if ($rechazado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Centro veterinario rechazado',
                    'data' => $centro
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el centro veterinario'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un centro veterinario
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $centro = CentroVeterinario::find($id);

            if (!$centro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Centro veterinario no encontrado'
                ], 404);
            }

            // Reglas de validación para actualización (excluyendo el centro actual)
            $rules = [
                'nombre' => 'required|string|max:255',
                'identificacion' => 'required|string|max:20|unique:centros_veterinarios,identificacion,' . $centro->id,
                'direccion' => 'required|string|max:500',
                'telefono' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:centros_veterinarios,email,' . $centro->id,
                'especialidades_medicas' => 'required|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'matricula_establecimiento' => 'nullable|string|max:100',
                'horarios_atencion' => 'nullable|string|max:100',
                'web_redes_sociales' => 'nullable|string|max:255',
            ];

            $validator = Validator::make($request->all(), $rules, CentroVeterinario::messages());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Procesar nuevo logo si se envió
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                // Eliminar logo anterior si existe
                if ($centro->logo_path) {
                    Storage::disk('public')->delete($centro->logo_path);
                }
                
                $logoPath = $request->file('logo')->store('logos/centros_veterinarios', 'public');
                $centro->logo_path = $logoPath;
            }

            // Actualizar los datos
            $centro->update([
                'nombre' => $request->nombre,
                'identificacion' => $request->identificacion,
                'direccion' => $request->direccion,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'especialidades_medicas' => $request->especialidades_medicas,
                'matricula_establecimiento' => $request->matricula_establecimiento,
                'horarios_atencion' => $request->horarios_atencion,
                'web_redes_sociales' => $request->web_redes_sociales,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Centro veterinario actualizado exitosamente',
                'data' => $centro
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un centro veterinario (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $centro = CentroVeterinario::find($id);

            if (!$centro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Centro veterinario no encontrado'
                ], 404);
            }

            $centro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Centro veterinario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar un centro veterinario eliminado
     */
    public function restaurar($id): JsonResponse
    {
        try {
            $centro = CentroVeterinario::withTrashed()->find($id);

            if (!$centro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Centro veterinario no encontrado'
                ], 404);
            }

            $centro->restore();

            return response()->json([
                'success' => true,
                'message' => 'Centro veterinario restaurado exitosamente',
                'data' => $centro
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el centro veterinario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de centros veterinarios
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $total = CentroVeterinario::count();
            $aprobados = CentroVeterinario::aprobados()->count();
            $pendientes = CentroVeterinario::pendientes()->count();
            $rechazados = CentroVeterinario::rechazados()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'aprobados' => $aprobados,
                    'pendientes' => $pendientes,
                    'rechazados' => $rechazados,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}