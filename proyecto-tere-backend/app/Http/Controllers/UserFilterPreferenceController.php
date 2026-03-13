<?php

namespace App\Http\Controllers;

use App\Models\PreferenciasFiltros;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserFilterPreferenceController extends Controller
{

    /**
     * Obtener todas las preferencias de filtros del usuario
     */
    public function index()
    {
        try {
            $preferencias = PreferenciasFiltros::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($pref) {
                    return [
                        'id' => $pref->id,
                        'nombre' => $pref->nombre_filtro,
                        'filtros' => $pref->getFiltrosFormateados(),
                        'fecha_creacion' => $pref->created_at->format('d/m/Y H:i'),
                        'es_activo' => $pref->es_activo
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $preferencias
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener preferencias de filtros: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las preferencias de filtros'
            ], 500);
        }
    }

    /**
     * Guardar una nueva preferencia de filtros
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre_filtro' => 'nullable|string|max:100',
                'filtros' => 'required|array',
                'filtros.especie' => 'nullable|array',
                'filtros.especie.*' => 'string',
                'filtros.sexo' => 'nullable|string|in:Macho,Hembra,Macho y Hembra',
                'filtros.edad' => 'nullable|array',
                'filtros.edad.*' => 'string|in:cachorro,joven,adulto,abuelo',
                'filtros.ubicacion' => 'nullable|string',
                'filtros.coordenadas.lat' => 'required_with:filtros.ubicacion|numeric',
                'filtros.coordenadas.lon' => 'required_with:filtros.ubicacion|numeric',
                'filtros.radio' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $filtros = $request->filtros;
            
            // Preparar datos para guardar
            $datosPreferencia = [
                'user_id' => Auth::id(),
                'nombre_filtro' => $request->nombre_filtro ?? 'Filtros guardados ' . now()->format('d/m/Y H:i'),
                'especies' => $filtros['especie'] ?? null,
                'rangos_edad' => $filtros['edad'] ?? null,
                'ubicacion_nombre' => $filtros['ubicacion'] ?? null,
                'radio_km' => $filtros['radio'] ?? 10
            ];

            // Procesar sexo
            if (!empty($filtros['sexo'])) {
                if ($filtros['sexo'] === 'Macho y Hembra') {
                    $datosPreferencia['sexos'] = ['macho', 'hembra'];
                } else {
                    $datosPreferencia['sexos'] = [strtolower($filtros['sexo'])];
                }
            }

            // Procesar coordenadas
            if (!empty($filtros['coordenadas'])) {
                $datosPreferencia['latitud'] = $filtros['coordenadas']['lat'];
                $datosPreferencia['longitud'] = $filtros['coordenadas']['lon'];
            }

            $preferencia = PreferenciasFiltros::create($datosPreferencia);

            return response()->json([
                'success' => true,
                'message' => 'Filtros guardados exitosamente',
                'data' => [
                    'id' => $preferencia->id,
                    'nombre' => $preferencia->nombre_filtro,
                    'filtros' => $preferencia->getFiltrosFormateados()
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al guardar preferencia de filtros: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los filtros'
            ], 500);
        }
    }

    /**
     * Obtener una preferencia específica
     */
    public function show($id)
    {
        try {
            $preferencia = PreferenciasFiltros::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$preferencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Preferencia no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $preferencia->id,
                    'nombre' => $preferencia->nombre_filtro,
                    'filtros' => $preferencia->getFiltrosFormateados()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener preferencia: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la preferencia'
            ], 500);
        }
    }

    /**
     * Cargar filtros de una preferencia
     */
    public function cargar($id)
    {
        try {
            $preferencia = PreferenciasFiltros::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$preferencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Preferencia no encontrada'
                ], 404);
            }

            // Formatear los filtros para aplicarlos
            $filtros = $preferencia->getFiltrosFormateados();

            return response()->json([
                'success' => true,
                'filtros' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar filtros: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los filtros'
            ], 500);
        }
    }

    /**
     * Actualizar una preferencia existente
     */
    public function update(Request $request, $id)
    {
        try {
            $preferencia = PreferenciasFiltros::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$preferencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Preferencia no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nombre_filtro' => 'sometimes|string|max:100',
                'filtros' => 'sometimes|array',
                'filtros.especie' => 'nullable|array',
                'filtros.especie.*' => 'string',
                'filtros.sexo' => 'nullable|string|in:Macho,Hembra,Macho y Hembra',
                'filtros.edad' => 'nullable|array',
                'filtros.edad.*' => 'string|in:cachorro,joven,adulto,abuelo',
                'filtros.ubicacion' => 'nullable|string',
                'filtros.coordenadas.lat' => 'required_with:filtros.ubicacion|numeric',
                'filtros.coordenadas.lon' => 'required_with:filtros.ubicacion|numeric',
                'filtros.radio' => 'nullable|integer|min:1|max:100',
                'es_activo' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar nombre si se proporcionó
            if ($request->has('nombre_filtro')) {
                $preferencia->nombre_filtro = $request->nombre_filtro;
            }

            // Actualizar estado activo
            if ($request->has('es_activo')) {
                $preferencia->es_activo = $request->es_activo;
            }

            // Actualizar filtros si se proporcionaron
            if ($request->has('filtros')) {
                $filtros = $request->filtros;
                
                $preferencia->especies = $filtros['especie'] ?? null;
                $preferencia->rangos_edad = $filtros['edad'] ?? null;
                $preferencia->ubicacion_nombre = $filtros['ubicacion'] ?? null;
                $preferencia->radio_km = $filtros['radio'] ?? 10;

                // Actualizar sexos
                if (isset($filtros['sexo'])) {
                    if ($filtros['sexo'] === 'Macho y Hembra') {
                        $preferencia->sexos = ['macho', 'hembra'];
                    } else {
                        $preferencia->sexos = [strtolower($filtros['sexo'])];
                    }
                }

                // Actualizar coordenadas
                if (isset($filtros['coordenadas'])) {
                    $preferencia->latitud = $filtros['coordenadas']['lat'];
                    $preferencia->longitud = $filtros['coordenadas']['lon'];
                }
            }

            $preferencia->save();

            return response()->json([
                'success' => true,
                'message' => 'Preferencia actualizada exitosamente',
                'data' => [
                    'id' => $preferencia->id,
                    'nombre' => $preferencia->nombre_filtro,
                    'filtros' => $preferencia->getFiltrosFormateados()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar preferencia: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la preferencia'
            ], 500);
        }
    }

    /**
     * Eliminar una preferencia
     */
    public function destroy($id)
    {
        try {
            $preferencia = PreferenciasFiltros::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$preferencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Preferencia no encontrada'
                ], 404);
            }

            $preferencia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Preferencia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar preferencia: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la preferencia'
            ], 500);
        }
    }

    /**
     * Establecer una preferencia como activa/principal
     */
    public function setActive($id)
    {
        try {
            // Primero, desactivar todas las preferencias del usuario
            PreferenciasFiltros::where('user_id', Auth::id())
                ->update(['es_activo' => false]);

            // Activar la preferencia seleccionada
            $preferencia = PreferenciasFiltros::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$preferencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Preferencia no encontrada'
                ], 404);
            }

            $preferencia->es_activo = true;
            $preferencia->save();

            return response()->json([
                'success' => true,
                'message' => 'Preferencia activada exitosamente',
                'data' => [
                    'id' => $preferencia->id,
                    'filtros' => $preferencia->getFiltrosFormateados()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al activar preferencia: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al activar la preferencia'
            ], 500);
        }
    }

    /**
     * Guardar filtros automáticamente (cuando el usuario aplica filtros)
     */
    public function storeAutomatic(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'filtros' => 'required|array',
                'filtros.especie' => 'nullable|array',
                'filtros.especie.*' => 'string',
                'filtros.sexo' => 'nullable|string|in:Macho,Hembra,Macho y Hembra',
                'filtros.edad' => 'nullable|array',
                'filtros.edad.*' => 'string|in:cachorro,joven,adulto,abuelo',
                'filtros.ubicacion' => 'nullable|string',
                'filtros.coordenadas.lat' => 'required_with:filtros.ubicacion|numeric',
                'filtros.coordenadas.lon' => 'required_with:filtros.ubicacion|numeric',
                'filtros.radio' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $filtros = $request->filtros;
            
            // Preparar datos para guardar
            $datosPreferencia = [
                'user_id' => Auth::id(),
                'nombre_filtro' => $request->nombre_filtro ?? 'Filtros automáticos ' . now()->format('d/m/Y H:i:s'),
                'especies' => $filtros['especie'] ?? null,
                'rangos_edad' => $filtros['edad'] ?? null,
                'ubicacion_nombre' => $filtros['ubicacion'] ?? null,
                'radio_km' => $filtros['radio'] ?? 10
            ];

            // Procesar sexo
            if (!empty($filtros['sexo'])) {
                if ($filtros['sexo'] === 'Macho y Hembra') {
                    $datosPreferencia['sexos'] = ['macho', 'hembra'];
                } else {
                    $datosPreferencia['sexos'] = [strtolower($filtros['sexo'])];
                }
            }

            // Procesar coordenadas
            if (!empty($filtros['coordenadas'])) {
                $datosPreferencia['latitud'] = $filtros['coordenadas']['lat'];
                $datosPreferencia['longitud'] = $filtros['coordenadas']['lon'];
            }

            // 🔥 Guardar solo si hay al menos un filtro
            if ($this->tieneFiltrosActivos($datosPreferencia)) {
                $this->limitarPreferenciasAutomaticas();
                
                // Desactivar todas las preferencias anteriores
                PreferenciasFiltros::where('user_id', Auth::id())
                    ->update(['es_activo' => false]);
                
                // Crear nueva preferencia como activa
                $datosPreferencia['es_activo'] = true;
                $preferencia = PreferenciasFiltros::create($datosPreferencia);
                
                Log::info('Filtros automáticos guardados para usuario: ' . Auth::id());
                
                return response()->json([
                    'success' => true,
                    'message' => 'Filtros procesados',
                    'data' => ['id' => $preferencia->id]
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Filtros procesados'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar filtros automáticos: ' . $e->getMessage());
            
            // No devolver error al cliente para no interrumpir la experiencia
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar filtros'
            ], 500);
        }
    }

    /**
     * Verificar si hay al menos un filtro activo
     */
    private function tieneFiltrosActivos($datos)
    {
        return !empty($datos['especies']) || 
               !empty($datos['sexos']) || 
               !empty($datos['rangos_edad']) || 
               !empty($datos['latitud']);
    }

    /**
     * Limitar a las últimas 20 preferencias automáticas
     */
    private function limitarPreferenciasAutomaticas()
    {
        $maxAutomaticas = 20;
        
        $automaticas = PreferenciasFiltros::where('user_id', Auth::id())
            ->where('nombre_filtro', 'like', 'Filtros automáticos%')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($automaticas->count() >= $maxAutomaticas) {
            // Eliminar las más antiguas
            $aEliminar = $automaticas->slice($maxAutomaticas - 1);
            foreach ($aEliminar as $pref) {
                $pref->delete();
            }
        }
    }

    /**
     * Obtener historial de filtros aplicados
     */
    public function historial()
    {
        try {
            $preferencias = PreferenciasFiltros::where('user_id', Auth::id())
                ->where('nombre_filtro', 'like', 'Filtros automáticos%')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($pref) {
                    return [
                        'id' => $pref->id,
                        'fecha' => $pref->created_at->format('d/m/Y H:i'),
                        'filtros' => $pref->getFiltrosFormateados()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $preferencias
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener historial: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }
}