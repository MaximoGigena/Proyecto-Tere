<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Mascota;
use App\Models\FichaMedica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FichaMedicaController extends Controller
{
    /**
     * Obtener la ficha médica de una mascota
     */
    public function show(Mascota $mascota)
    {
        try {
            // Asegurar que la mascota tiene ficha médica
            $fichaMedica = $mascota->fichaMedica;
            
            if (!$fichaMedica) {
                // Crear ficha médica por defecto
                $fichaMedica = $mascota->fichaMedica()->create();
            }
            
            // Cargar relaciones necesarias
            $fichaMedica->load('mascota');
            
            // Construir respuesta manualmente
            return response()->json([
                'success' => true,
                'data' => [
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'especie' => $mascota->especie,
                        'raza' => $mascota->caracteristicas->raza ?? 'No especificada',
                        'edad_formateada' => $mascota->edad_formateada,
                        'sexo' => $mascota->sexo,
                        'castrado' => $mascota->castrado,
                        'foto_principal_url' => $mascota->foto_principal_url,
                    ],
                    'ficha_medica' => [
                        'id' => $fichaMedica->id,
                        'color_y_senas' => $fichaMedica->color_y_senas ?? 'No registrado',
                        'peso' => [
                            'valor' => $fichaMedica->peso_actual,
                            'formateado' => $fichaMedica->peso_formateado ?? 'No registrado',
                            'ultima_actualizacion' => $fichaMedica->fecha_ultima_actualizacion_peso 
                                ? $fichaMedica->fecha_ultima_actualizacion_peso->format('d/m/Y')
                                : null
                        ],
                        'tipo_sanguineo' => $fichaMedica->tipo_sanguineo ?? 'No registrado',
                        'numero_chip' => $fichaMedica->numero_chip ?? 'No registrado',
                        'ultima_actualizacion_ficha' => $fichaMedica->updated_at->format('d/m/Y H:i')
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la ficha médica',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar la ficha médica
     */
    public function update(Request $request, Mascota $mascota)
    {
        try {
            // Preparar reglas de validación dinámicamente
            $rules = [
                'color_y_senas' => 'nullable|string|max:500',
                'peso_actual' => 'nullable|numeric|min:0|max:200',
                'tipo_sanguineo' => 'nullable|string|max:20',
            ];
            
            // Solo validar unique si se envía numero_chip y no es null/vacío
            if ($request->has('numero_chip') && !empty($request->numero_chip)) {
                $rules['numero_chip'] = 'nullable|string|max:50|unique:ficha_medica,numero_chip,' . ($mascota->fichaMedica->id ?? 'NULL');
            } else {
                $rules['numero_chip'] = 'nullable|string|max:50';
            }
            
            $validated = $request->validate($rules);

            DB::beginTransaction();

            // Obtener o crear ficha médica
            $fichaMedica = $mascota->fichaMedica;
            
            if (!$fichaMedica) {
                $fichaMedica = $mascota->fichaMedica()->create();
            }

            // Actualizar datos (manejar null correctamente)
            $dataToUpdate = [];
            
            if ($request->has('color_y_senas')) {
                $dataToUpdate['color_y_senas'] = $request->color_y_senas;
            }
            
            if ($request->has('peso_actual')) {
                $dataToUpdate['peso_actual'] = $request->peso_actual;
                $dataToUpdate['fecha_ultima_actualizacion_peso'] = now();
            }
            
            if ($request->has('tipo_sanguineo')) {
                $dataToUpdate['tipo_sanguineo'] = $request->tipo_sanguineo;
            }
            
            if ($request->has('numero_chip')) {
                // Permitir actualizar a null o vacío
                $dataToUpdate['numero_chip'] = $request->numero_chip ?: null;
            }

            // Solo actualizar si hay datos
            if (!empty($dataToUpdate)) {
                $fichaMedica->update($dataToUpdate);
            }
            
            // Recargar la relación
            $fichaMedica->refresh();
            $fichaMedica->load('mascota');

            DB::commit();

            // Respuesta exitosa (igual que antes)
            return response()->json([
                'success' => true,
                'message' => 'Ficha médica actualizada correctamente',
                'data' => [
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'especie' => $mascota->especie,
                        'raza' => $mascota->caracteristicas->raza ?? 'No especificada',
                        'edad_formateada' => $mascota->edad_formateada,
                        'sexo' => $mascota->sexo,
                        'castrado' => $mascota->castrado,
                        'foto_principal_url' => $mascota->foto_principal_url,
                    ],
                    'ficha_medica' => [
                        'id' => $fichaMedica->id,
                        'color_y_senas' => $fichaMedica->color_y_senas ?? 'No registrado',
                        'peso' => [
                            'valor' => $fichaMedica->peso_actual,
                            'formateado' => $fichaMedica->peso_formateado ?? 'No registrado',
                            'ultima_actualizacion' => $fichaMedica->fecha_ultima_actualizacion_peso 
                                ? $fichaMedica->fecha_ultima_actualizacion_peso->format('d/m/Y')
                                : null
                        ],
                        'tipo_sanguineo' => $fichaMedica->tipo_sanguineo ?? 'No registrado',
                        'numero_chip' => $fichaMedica->numero_chip ?? 'No registrado',
                        'ultima_actualizacion_ficha' => $fichaMedica->updated_at->format('d/m/Y H:i')
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error para depuración
            Log::error('Error en update ficha médica: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la ficha médica',
                'error' => $e->getMessage() // Esto te mostrará el error real
            ], 500);
        }
    }

    /**
     * Actualizar solo el peso (método específico)
     */
    public function updatePeso(Request $request, Mascota $mascota)
    {
        try {
            $validated = $request->validate([
                'peso' => 'required|numeric|min:0|max:200'
            ]);

            $fichaMedica = $mascota->fichaMedica()->firstOrCreate();
            $fichaMedica->actualizarPeso($validated['peso']);
            
            // Recargar para obtener datos actualizados
            $fichaMedica->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Peso actualizado correctamente',
                'data' => [
                    'peso_actual' => $fichaMedica->peso_actual,
                    'peso_formateado' => $fichaMedica->peso_formateado,
                    'fecha_actualizacion' => $fichaMedica->fecha_ultima_actualizacion_peso->format('d/m/Y')
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el peso',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar solo el número de chip
     */
    public function updateChip(Request $request, Mascota $mascota)
    {
        try {
            $validated = $request->validate([
                'numero_chip' => 'required|string|max:50|unique:ficha_medica,numero_chip,' . ($mascota->fichaMedica->id ?? 'NULL')
            ]);

            $fichaMedica = $mascota->fichaMedica()->firstOrCreate();
            $fichaMedica->update([
                'numero_chip' => $validated['numero_chip']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Número de chip actualizado correctamente',
                'data' => [
                    'numero_chip' => $fichaMedica->numero_chip
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el chip',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar solo el tipo sanguíneo
     */
    public function updateTipoSanguineo(Request $request, Mascota $mascota)
    {
        try {
            $validated = $request->validate([
                'tipo_sanguineo' => 'required|string|max:20'
            ]);

            $fichaMedica = $mascota->fichaMedica()->firstOrCreate();
            $fichaMedica->update([
                'tipo_sanguineo' => $validated['tipo_sanguineo']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo sanguíneo actualizado correctamente',
                'data' => [
                    'tipo_sanguineo' => $fichaMedica->tipo_sanguineo
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo sanguíneo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar color y señas particulares
     */
    public function updateColorSenas(Request $request, Mascota $mascota)
    {
        try {
            $validated = $request->validate([
                'color_y_senas' => 'required|string|max:500'
            ]);

            $fichaMedica = $mascota->fichaMedica()->firstOrCreate();
            $fichaMedica->update([
                'color_y_senas' => $validated['color_y_senas']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Color y señas actualizados correctamente',
                'data' => [
                    'color_y_senas' => $fichaMedica->color_y_senas
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar color y señas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}