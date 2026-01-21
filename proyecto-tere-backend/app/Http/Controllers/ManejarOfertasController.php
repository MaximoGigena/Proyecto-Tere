<?php

namespace App\Http\Controllers;

use App\Models\OfertaAdopcion;
use App\Models\InteraccionSwipeUsuario;
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
            // Buscar la oferta de adopción con TODAS las relaciones
            $oferta = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual' // ✅ Añadir ubicación
            ])
            ->where('id_oferta', $idOferta)
            ->where('estado_oferta', 'publicada')
            ->firstOrFail();

            // Preparar la respuesta con la información completa
            $mascota = $oferta->mascota;
            
            // ✅ Obtener ubicación del tutor
            $ubicacionTutor = null;
            $ubicacionTexto = 'Ubicación no disponible';
            
            if ($mascota->usuario && 
                $mascota->usuario->user && 
                $mascota->usuario->user->ubicacionActual) {
                
                $ubicacion = $mascota->usuario->user->ubicacionActual;
                $ubicacionTutor = [
                    'latitude' => $ubicacion->latitude,
                    'longitude' => $ubicacion->longitude,
                    'city' => $ubicacion->city,
                    'state' => $ubicacion->state,
                    'country' => $ubicacion->country,
                    'country_code' => $ubicacion->country_code,
                    'accuracy' => $ubicacion->accuracy,
                    'location_updated_at' => $ubicacion->location_updated_at
                ];
                
                $parts = [];
                if ($ubicacion->city) $parts[] = $ubicacion->city;
                if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                    $parts[] = $ubicacion->state;
                }
                if ($ubicacion->country) $parts[] = $ubicacion->country;
                
                $ubicacionTexto = implode(', ', $parts);
            }
            
            // Determinar rango etario
            $rangoEtario = 'Adulto';
            if ($mascota->edadRelacion && $mascota->edadRelacion->dias !== null) {
                $rangoEtario = FiltrosMascotasController::determinarRangoEtario(
                    $mascota->especie,
                    $mascota->edadRelacion->dias
                );
            }
            
            $datosMascota = [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'sexo' => $mascota->sexo,
                'castrado' => $mascota->castrado,
                'rango_etario' => $rangoEtario,
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
                ],
                'foto_principal_url' => $mascota->foto_principal_url,
                // ✅ INCLUIR UBICACIÓN
                'ubicacion' => $ubicacionTutor,
                'ubicacion_texto' => $ubicacionTexto
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
            Log::info('=== OBTENER OFERTAS DISPONIBLES ===');
            Log::info('Usuario autenticado:', ['usuario_id' => Auth::id()]);
            Log::info('Parámetros recibidos:', $request->all());
            
            $usuarioAutenticado = Auth::user();
            
            // ✅ Obtener ubicación del usuario para filtrar por distancia
            $ubicacionUsuario = null;
            if ($usuarioAutenticado->ubicacionActual) {
                $ubicacionUsuario = [
                    'latitude' => $usuarioAutenticado->ubicacionActual->latitude,
                    'longitude' => $usuarioAutenticado->ubicacionActual->longitude,
                    'city' => $usuarioAutenticado->ubicacionActual->city,
                    'state' => $usuarioAutenticado->ubicacionActual->state,
                    'country' => $usuarioAutenticado->ubicacionActual->country
                ];
            }
            
            Log::info('Ubicación usuario autenticado:', $ubicacionUsuario);
            
            // ✅ Consulta base con TODAS las relaciones necesarias
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual'
            ])
            ->where('estado_oferta', 'publicada');
            
            Log::info('Consulta base creada. Total ofertas publicadas:', [
                'count' => $query->count()
            ]);
            
            // ✅ Aplicar filtros
            $filtrosController = new FiltrosMascotasController();
            $query = $filtrosController->aplicarFiltros($query, $request);
            
            // ✅ Filtrar por distancia si es necesario
            if ($ubicacionUsuario && $request->has('distancia_maxima')) {
                $distanciaMaxima = $request->distancia_maxima ?: 50;
                
                $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($ubicacionUsuario, $distanciaMaxima) {
                    $lat = $ubicacionUsuario['latitude'];
                    $lon = $ubicacionUsuario['longitude'];
                    
                    $q->whereRaw(
                        "ST_Distance_Sphere(
                            location,
                            ST_GeomFromText(?, 4326)
                        ) <= ?",
                        ["POINT({$lon} {$lat})", $distanciaMaxima * 1000]
                    );
                });
            }
            
            $ofertas = $query->orderBy('created_at', 'desc')->get();
            
            Log::info('Ofertas encontradas después de filtros:', [
                'count' => $ofertas->count()
            ]);
            
            // ✅ Procesar cada oferta CON TODOS LOS DATOS
            $ofertasFormateadas = $ofertas->map(function($oferta) use ($ubicacionUsuario) {
                try {
                    $mascota = $oferta->mascota;
                    
                    if (!$mascota) {
                        Log::warning('Oferta sin mascota: ' . $oferta->id_oferta);
                        return null;
                    }
                    
                    // ✅ Obtener ubicación del tutor
                    $ubicacionTexto = 'Ubicación no disponible';
                    $ubicacionTutor = null;
                    
                    Log::debug('Verificando relaciones para mascota: ' . $mascota->id, [
                        'tiene_usuario' => !is_null($mascota->usuario),
                        'usuario_id' => $mascota->usuario_id,
                        'tiene_user' => $mascota->usuario ? !is_null($mascota->usuario->user) : false,
                        'tiene_ubicacionActual' => $mascota->usuario && $mascota->usuario->user ? 
                            !is_null($mascota->usuario->user->ubicacionActual) : false
                    ]);
                    
                    if ($mascota->usuario && 
                        $mascota->usuario->user && 
                        $mascota->usuario->user->ubicacionActual) {
                        
                        $ubicacion = $mascota->usuario->user->ubicacionActual;
                        
                        // Datos completos de ubicación
                        $ubicacionTutor = [
                            'latitude' => $ubicacion->latitude,
                            'longitude' => $ubicacion->longitude,
                            'city' => $ubicacion->city,
                            'state' => $ubicacion->state,
                            'country' => $ubicacion->country,
                            'country_code' => $ubicacion->country_code,
                            'accuracy' => $ubicacion->accuracy,
                            'location_updated_at' => $ubicacion->location_updated_at
                        ];
                        
                        // Formatear texto de ubicación
                        $parts = [];
                        if ($ubicacion->city) $parts[] = $ubicacion->city;
                        if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                            $parts[] = $ubicacion->state;
                        }
                        if ($ubicacion->country) $parts[] = $ubicacion->country;
                        
                        $ubicacionTexto = implode(', ', $parts);
                        
                        // Calcular distancia si el usuario autenticado tiene ubicación
                        if ($ubicacionUsuario) {
                            $distancia = $this->calcularDistancia(
                                $ubicacionUsuario['latitude'],
                                $ubicacionUsuario['longitude'],
                                $ubicacion->latitude,
                                $ubicacion->longitude
                            );
                            $ubicacionTutor['distancia_km'] = round($distancia, 1);
                            $ubicacionTutor['distancia_texto'] = $this->formatearDistancia($distancia);
                        }
                    }
                    
                    // ✅ Obtener foto principal
                    $fotoPrincipal = $mascota->fotos->first();
                    $fotoPrincipalUrl = $fotoPrincipal ? $fotoPrincipal->url : null;
                    
                    if (!$fotoPrincipalUrl && $mascota->fotos->isNotEmpty()) {
                        $fotoPrincipalUrl = $mascota->fotos->first()->url;
                    }
                    
                    // ✅ Determinar rango etario
                    $rangoEtario = 'Adulto';
                    if ($mascota->edadRelacion && $mascota->edadRelacion->dias !== null) {
                        $rangoEtario = FiltrosMascotasController::determinarRangoEtario(
                            $mascota->especie,
                            $mascota->edadRelacion->dias
                        );
                    }
                    
                    // ✅ Preparar fotos
                    $fotos = $mascota->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => $foto->url,
                            'es_principal' => $foto->es_principal,
                            'ruta_foto' => $foto->ruta_foto
                        ];
                    })->toArray();
                    
                    return [
                        'id_oferta' => $oferta->id_oferta,
                        'estado_oferta' => $oferta->estado_oferta,
                        'mascota' => [
                            'id' => $mascota->id,
                            'nombre' => $mascota->nombre,
                            'especie' => $mascota->especie,
                            'sexo' => $mascota->sexo,
                            'castrado' => $mascota->castrado,
                            'rango_etario' => $rangoEtario,
                            'foto_principal_url' => $fotoPrincipalUrl,
                            'caracteristicas' => $mascota->caracteristicas,
                            'ubicacion_texto' => $ubicacionTexto,
                            'ubicacion' => $ubicacionTutor,
                            'edad_formateada' => $mascota->edad_formateada,
                            'fotos' => $fotos,
                            'usuario_id' => $mascota->usuario_id
                        ],
                        'distancia' => $ubicacionTutor['distancia_texto'] ?? null
                    ];
                    
                } catch (\Exception $e) {
                    Log::error('Error procesando oferta ' . ($oferta->id_oferta ?? 'unknown') . ': ' . $e->getMessage());
                    return null;
                }
            })->filter()->values();

            Log::info('Respuesta final preparada, ofertas encontradas:', [
                'count' => $ofertasFormateadas->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'count' => $ofertasFormateadas->count(),
                'debug' => [
                    'request_params' => $request->all(),
                    'total_filtered' => $ofertasFormateadas->count(),
                    'tiene_ubicacion_usuario' => !is_null($ubicacionUsuario),
                    'ubicacion_usuario' => $ubicacionUsuario
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener ofertas de adopción: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las ofertas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular distancia entre dos puntos en kilómetros
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en kilómetros
        
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }

    /**
     * Formatear distancia para mostrar
     */
    private function formatearDistancia($distanciaKm)
    {
        if ($distanciaKm < 1) {
            $metros = round($distanciaKm * 1000);
            return "{$metros} m";
        } elseif ($distanciaKm < 10) {
            return round($distanciaKm, 1) . " km";
        } else {
            return round($distanciaKm) . " km";
        }
    }

    /**
     * Registrar interacción de usuario (like/dislike)
     */
    public function registrarInteraccion(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            $validated = $request->validate([
                'mascota_id' => 'required|integer|exists:mascotas,id',
                'tipo_interaccion' => 'required|in:like,dislike,vista',
                'oferta_id' => 'nullable|integer|exists:ofertas_adopcion,id_oferta'
            ]);
            
            $interaccion = InteraccionSwipeUsuario::updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'mascota_id' => $validated['mascota_id'],
                    'oferta_id' => $validated['oferta_id'] ?? null,
                ],
                [
                    'tipo_interaccion' => $validated['tipo_interaccion'],
                    'fecha_interaccion' => now(),
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Interacción registrada correctamente',
                'data' => $interaccion
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error registrando interacción: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar interacción: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener ofertas para el sistema de swipe
     */
    public function obtenerOfertasParaSwipe(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            $mascotasInteractuadas = InteraccionSwipeUsuario::where('usuario_id', $usuario->id)
                ->pluck('mascota_id')
                ->toArray();
            
            // Consulta base con ubicación
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual' // ✅ Añadir ubicación
            ])
            ->where('estado_oferta', 'publicada')
            ->where('id_usuario_responsable', '!=', $usuario->id);
            
            // Excluir mascotas ya interactuadas
            if (!empty($mascotasInteractuadas)) {
                $query->whereHas('mascota', function($q) use ($mascotasInteractuadas) {
                    $q->whereNotIn('id', $mascotasInteractuadas);
                });
            }
            
            // Aplicar filtros si existen
            if ($request->has('filtros')) {
                $filtros = json_decode($request->filtros, true);
                
                if (is_array($filtros)) {
                    $tempRequest = new Request($filtros);
                    $filtrosController = new FiltrosMascotasController();
                    $query = $filtrosController->aplicarFiltros($query, $tempRequest);
                }
            }
            
            $ofertas = $query->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($oferta) {
                    $mascota = $oferta->mascota;
                    
                    $fotos = $mascota->fotos ?? collect([]);
                    
                    // ✅ Obtener ubicación
                    $ubicacionTutor = null;
                    $ubicacionTexto = 'Ubicación no disponible';
                    
                    if ($mascota->usuario && 
                        $mascota->usuario->user && 
                        $mascota->usuario->user->ubicacionActual) {
                        
                        $ubicacion = $mascota->usuario->user->ubicacionActual;
                        $ubicacionTutor = [
                            'latitude' => $ubicacion->latitude,
                            'longitude' => $ubicacion->longitude,
                            'city' => $ubicacion->city,
                            'state' => $ubicacion->state,
                            'country' => $ubicacion->country,
                            'country_code' => $ubicacion->country_code
                        ];
                        
                        $parts = [];
                        if ($ubicacion->city) $parts[] = $ubicacion->city;
                        if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                            $parts[] = $ubicacion->state;
                        }
                        if ($ubicacion->country) $parts[] = $ubicacion->country;
                        
                        $ubicacionTexto = implode(', ', $parts);
                    }
                    
                    // Determinar rango etario
                    $rangoEtario = 'Adulto';
                    if ($mascota->edadRelacion && $mascota->edadRelacion->dias !== null) {
                        $rangoEtario = FiltrosMascotasController::determinarRangoEtario(
                            $mascota->especie,
                            $mascota->edadRelacion->dias
                        );
                    }
                    
                    return [
                        'id_oferta' => $oferta->id_oferta,
                        'estado_oferta' => $oferta->estado_oferta,
                        'permiso_historial_medico' => $oferta->permiso_historial_medico,
                        'permiso_contacto_tutor' => $oferta->permiso_contacto_tutor,
                        'created_at' => $oferta->created_at,
                        'mascota' => [
                            'id' => $mascota->id,
                            'nombre' => $mascota->nombre,
                            'especie' => $mascota->especie,
                            'sexo' => $mascota->sexo,
                            'castrado' => $mascota->castrado,
                            'rango_etario' => $rangoEtario,
                            'fecha_nacimiento' => $mascota->fecha_nacimiento,
                            'usuario_id' => $mascota->usuario_id,
                            'caracteristicas' => $mascota->caracteristicas,
                            'fotos' => $fotos->map(function($foto) {
                                return [
                                    'id' => $foto->id,
                                    'url' => asset('storage/' . $foto->ruta_foto),
                                    'es_principal' => $foto->es_principal ?? false,
                                    'ruta_foto' => $foto->ruta_foto
                                ];
                            })->toArray(),
                            'edad' => $mascota->edadRelacion ? [
                                'dias' => $mascota->edadRelacion->dias,
                                'meses' => $mascota->edadRelacion->meses,
                                'años' => $mascota->edadRelacion->años,
                                'edad_formateada' => $mascota->edadRelacion->edad_formateada
                            ] : null,
                            'edad_formateada' => $mascota->edad_formateada ?? 'Edad no disponible',
                            'usuario' => $mascota->usuario ? [
                                'id' => $mascota->usuario->id,
                                'nombre' => $mascota->usuario->nombre,
                            ] : null,
                            'foto_principal_url' => $mascota->foto_principal_url ?? null,
                            // ✅ INCLUIR UBICACIÓN
                            'ubicacion' => $ubicacionTutor,
                            'ubicacion_texto' => $ubicacionTexto
                        ]
                    ];
                });
            
            Log::info('Ofertas para swipe obtenidas', [
                'usuario_id' => $usuario->id,
                'total_ofertas' => $ofertas->count(),
                'mascotas_interactuadas_count' => count($mascotasInteractuadas)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $ofertas,
                'count' => $ofertas->count(),
                'debug' => [
                    'usuario_id' => $usuario->id,
                    'mascotas_interactuadas' => $mascotasInteractuadas
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo ofertas para swipe: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar ofertas: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }

    /**
     * Obtener ofertas ordenadas por proximidad
     */
    public function obtenerOfertasProximidad(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Verificar si el usuario tiene ubicación
            $ubicacionUsuario = $user->ubicacionActual;
            
            if (!$ubicacionUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Necesitas permitir el acceso a tu ubicación para ver mascotas cerca de ti.'
                ], 400);
            }
            
            // Usar el método de cálculo de distancia
            $ofertas = $this->obtenerOfertasConDistancia($ubicacionUsuario, $request);
            
            // Ordenar por distancia
            $ofertas = $ofertas->sortBy('distancia_km')->values();
            
            return response()->json([
                'success' => true,
                'data' => $ofertas,
                'ubicacion_usuario' => [
                    'latitude' => $ubicacionUsuario->latitude,
                    'longitude' => $ubicacionUsuario->longitude,
                    'city' => $ubicacionUsuario->city,
                    'state' => $ubicacionUsuario->state
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en obtenerOfertasProximidad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ofertas por proximidad'
            ], 500);
        }
    }

    private function obtenerOfertasConDistancia($ubicacionUsuario, $request)
    {
        $lat = $ubicacionUsuario->latitude;
        $lon = $ubicacionUsuario->longitude;
        
        return OfertaAdopcion::with([
            'mascota.fotos',
            'mascota.caracteristicas',
            'mascota.edadRelacion',
            'mascota.usuario.user.ubicacionActual'
        ])
        ->where('estado_oferta', 'publicada')
        ->whereHas('mascota.usuario.user.ubicacionActual')
        ->get()
        ->map(function($oferta) use ($lat, $lon) {
            $ubicacionTutor = $oferta->mascota->usuario->user->ubicacionActual;
            
            if (!$ubicacionTutor) {
                $oferta->distancia_km = null;
                return $oferta;
            }
            
            // Calcular distancia
            $distancia = $this->calcularDistancia(
                $lat, $lon,
                $ubicacionTutor->latitude,
                $ubicacionTutor->longitude
            );
            
            $oferta->distancia_km = $distancia;
            $oferta->distancia_texto = $this->formatearDistancia($distancia);
            $oferta->ubicacion_texto = $this->formatearUbicacionTexto([
                'city' => $ubicacionTutor->city,
                'state' => $ubicacionTutor->state,
                'country' => $ubicacionTutor->country
            ]);
            
            return $oferta;
        })
        ->filter(function($oferta) use ($request) {
            // Filtrar por distancia máxima si se especifica
            if ($request->has('distancia_maxima') && $oferta->distancia_km !== null) {
                return $oferta->distancia_km <= $request->distancia_maxima;
            }
            return true;
        });
    }
}