<?php

namespace App\Http\Controllers;

use App\Models\OfertaAdopcion;
use App\Models\Mascota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ManejarOfertasController extends Controller
{
    /**
     * Obtener una oferta de adopción específica con información de la mascota
     */
    public function obtenerOferta($idOferta)
    {
        try {
            // Buscar la oferta de adopción
            $oferta = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario'
            ])
            ->where('id_oferta', $idOferta)
            ->where('estado_oferta', 'publicada')
            ->firstOrFail();

            // Verificar que el usuario autenticado tenga permisos para ver esta oferta
            $usuarioAutenticado = Auth::user();
            
            // Puedes agregar lógica de permisos aquí si es necesario
            // Por ejemplo, verificar distancia, etc.

            // Preparar la respuesta con la información completa
            $mascota = $oferta->mascota;
            
            $datosMascota = [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'sexo' => $mascota->sexo,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'usuario_id' => $mascota->usuario_id,
                'caracteristicas' => $mascota->caracteristicas,
                'fotos' => $mascota->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => asset('storage/' . $foto->ruta_foto),
                        'es_principal' => $foto->es_principal,
                        'ruta_foto' => $foto->ruta_foto
                    ];
                }),
                'edad' => $mascota->edadRelacion ? [
                    'dias' => $mascota->edadRelacion->dias,
                    'meses' => $mascota->edadRelacion->meses,
                    'años' => $mascota->edadRelacion->años,
                    'edad_formateada' => $mascota->edadRelacion->edad_formateada
                ] : null,
                'edad_formateada' => $mascota->edad_formateada,
                'usuario' => [
                    'id' => $mascota->usuario->id,
                    'nombre' => $mascota->usuario->nombre,
                    // Agrega más campos del usuario si son necesarios
                ],
                'foto_principal_url' => $mascota->foto_principal_url,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'oferta' => [
                        'id_oferta' => $oferta->id_oferta,
                        'estado_oferta' => $oferta->estado_oferta,
                        'permiso_historial_medico' => $oferta->permiso_historial_medico,
                        'permiso_contacto_tutor' => $oferta->permiso_contacto_tutor,
                        'created_at' => $oferta->created_at,
                    ],
                    'mascota' => $datosMascota
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Oferta de adopción no encontrada o no está disponible'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al obtener oferta de adopción: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la oferta de adopción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ofertas disponibles para adopción (para la vista de "Mascotas cerca de ti")
     */
    public function obtenerOfertasDisponibles(Request $request)
    {
        try {
            $usuarioAutenticado = Auth::user();
            
            // Aplicar filtros si existen
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion'
            ])
            ->where('estado_oferta', 'publicada');
            
            // Filtro por especie
            if ($request->has('especie') && $request->especie) {
                $query->whereHas('mascota', function($q) use ($request) {
                    $q->where('especie', $request->especie);
                });
            }
            
            // Filtro por sexo
            if ($request->has('sexo') && $request->sexo) {
                $query->whereHas('mascota', function($q) use ($request) {
                    $q->where('sexo', $request->sexo);
                });
            }
            
            // Filtro por tamaño
            if ($request->has('tamano') && $request->tamano) {
                $query->whereHas('mascota.caracteristicas', function($q) use ($request) {
                    $q->where('tamano', $request->tamano);
                });
            }
            
            // Filtro por rango de edad
            if ($request->has('rangos_edad') && $request->rangos_edad) {
                $rangos = json_decode($request->rangos_edad, true);
                
                if (is_array($rangos) && count($rangos) > 0) {
                    $query->whereHas('mascota.edadRelacion', function($q) use ($rangos) {
                        $condiciones = [];
                        
                        foreach ($rangos as $rango) {
                            if ($rango === 'cachorro') {
                                $condiciones[] = ['dias', '<', 365]; // Menos de 1 año
                            } elseif ($rango === 'joven') {
                                $condiciones[] = ['dias', '>=', 365];
                                $condiciones[] = ['dias', '<', 1825]; // 1-5 años
                            } elseif ($rango === 'adulto') {
                                $condiciones[] = ['dias', '>=', 1825];
                                $condiciones[] = ['dias', '<', 5475]; // 5-15 años
                            } elseif ($rango === 'senior') {
                                $condiciones[] = ['dias', '>=', 5475];
                            }
                        }
                        
                        if (!empty($condiciones)) {
                            $q->where(function($query) use ($condiciones) {
                                foreach ($condiciones as $condicion) {
                                    $query->orWhere($condicion[0], $condicion[1], $condicion[2]);
                                }
                            });
                        }
                    });
                }
            }
            
            // Obtener ofertas
            $ofertas = $query->get()->map(function($oferta) {
                $mascota = $oferta->mascota;
                
                // Determinar rango etario para mostrar
                $rangoEtario = 'Adulto';
                if ($mascota->edadRelacion) {
                    $dias = $mascota->edadRelacion->dias;
                    if ($dias < 365) {
                        $rangoEtario = 'Cachorro';
                    } elseif ($dias < 1825) { // 5 años
                        $rangoEtario = 'Joven';
                    } elseif ($dias >= 5475) { // 15 años
                        $rangoEtario = 'Senior';
                    }
                }
                
                return [
                    'id_oferta' => $oferta->id_oferta,
                    'estado_oferta' => $oferta->estado_oferta,
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'especie' => $mascota->especie,
                        'sexo' => $mascota->sexo,
                        'rango_etario' => $rangoEtario,
                        'foto_principal_url' => $mascota->foto_principal_url,
                        'caracteristicas' => $mascota->caracteristicas
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ofertas,
                'count' => $ofertas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener ofertas de adopción: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las ofertas: ' . $e->getMessage()
            ], 500);
        }
    }
}