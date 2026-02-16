<?php

namespace App\Http\Controllers;

use App\Models\OfertaAdopcion;
use App\Models\InteraccionSwipeUsuario;
use App\Models\Mascota;
use App\Helpers\UbicacionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        Log::info('📌 EJECUTANDO obtenerOfertasDisponibles() 📌');
        try {
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
            
            // ✅ Consulta base con TODAS las relaciones necesarias
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual'
            ])
            ->where('estado_oferta', 'publicada');

            // ✅ EXCLUIR ofertas del usuario autenticado
            $usuario = $usuarioAutenticado->userable;
            $query->where('id_usuario_responsable', '!=', $usuario->id)
                ->whereDoesntHave('mascota', function($q) use ($usuarioAutenticado) {
                    $q->where('usuario_id', $usuarioAutenticado->id);
                });

            // ✅ Aplicar filtros estándar (especie, sexo, edad)
            $filtrosController = new FiltrosMascotasController();
            $query = $filtrosController->aplicarFiltros($query, $request);
            
            // ✅ FILTRO POR UBICACIÓN ESPECÍFICA (NUEVO)
            if ($request->has('latitud') && $request->has('longitud')) {
                $lat = $request->latitud;
                $lon = $request->longitud;
                $radio = $request->radio_km ?? 10; // Radio en kilómetros (por defecto 10km)
                
                Log::info('Aplicando filtro por ubicación específica:', [
                    'latitud' => $lat,
                    'longitud' => $lon,
                    'radio_km' => $radio,
                    'ubicacion_nombre' => $request->ubicacion ?? 'Sin nombre'
                ]);
                
                // Filtrar por distancia usando cálculo esférico
                $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($lat, $lon, $radio) {
                    // Calcular distancia usando Haversine formula
                    $q->whereRaw(
                        "ST_DWithin(
                            location::geography,
                            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                            ?
                        )",
                        [$lon, $lat, $radio * 1000]
                    );
                });
            }
            // ✅ FILTRO POR DISTANCIA DESDE LA UBICACIÓN DEL USUARIO (existente)
            elseif ($ubicacionUsuario && $request->has('distancia_maxima')) {
                $distanciaMaxima = $request->distancia_maxima ?: 50;
                $lat = $ubicacionUsuario['latitude'];
                $lon = $ubicacionUsuario['longitude'];
                
                $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($lat, $lon, $distanciaMaxima) {
                    $q->whereRaw(
                        "ST_DWithin(
                            location::geography,
                            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                            ?
                        )",
                        [$lon, $lat, $distanciaMaxima * 1000]
                    );
                });
            }
            // ✅ FILTRO POR CIUDAD/PROVINCIA (NUEVO - alternativa)
            elseif ($request->has('ciudad') || $request->has('provincia')) {
                $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($request) {
                    if ($request->has('ciudad')) {
                        $q->where('city', 'like', '%' . $request->ciudad . '%');
                    }
                    if ($request->has('provincia')) {
                        $q->where('state', 'like', '%' . $request->provincia . '%');
                    }
                });
            }
            
            // Ordenar resultados
            if ($request->has('latitud') && $request->has('longitud')) {
                // Si hay ubicación específica, ordenar por proximidad
                $lat = $request->latitud;
                $lon = $request->longitud;
                
                // Agregar selección de distancia para ordenamiento
                $query->select('*')
                    ->addSelect(DB::raw(
                        "ST_Distance(
                            location::geography,
                            ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography
                        ) / 1000 as distancia_km"
                    ))
                    ->orderBy('distancia_km', 'asc')
                    ->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            $ofertas = $query->get();
            
            // ✅ Procesar cada oferta CON DISTANCIA CALCULADA
            $ofertasFormateadas = $ofertas->map(function($oferta) use ($ubicacionUsuario, $request) {
                try {
                    $mascota = $oferta->mascota;
                    
                    if (!$mascota) {
                        return null;
                    }
                    
                    // ✅ Obtener ubicación del tutor
                    $ubicacionTexto = 'Ubicación no disponible';
                    $ubicacionTutor = null;
                    $distanciaCalculada = null;
                    
                    if ($mascota->usuario && 
                        $mascota->usuario->user && 
                        $ubicacion = $mascota->usuario->user->ubicacionActual) {
                        
                        // Datos completos de ubicación
                        $ubicacionTutor = [
                            'latitude' => $ubicacion->latitude,
                            'longitude' => $ubicacion->longitude,
                            'city' => $ubicacion->city,
                            'state' => $ubicacion->state,
                            'country' => $ubicacion->country,
                            'country_code' => $ubicacion->country_code,
                        ];
                        
                        // Formatear texto de ubicación
                        $parts = [];
                        if ($ubicacion->city) $parts[] = $ubicacion->city;
                        if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                            $parts[] = $ubicacion->state;
                        }
                        if ($ubicacion->country) $parts[] = $ubicacion->country;
                        $ubicacionTexto = implode(', ', $parts);
                        
                        // Calcular distancia según el origen
                        if ($request->has('latitud') && $request->has('longitud')) {
                            // Desde ubicación específica del filtro
                            $distancia = $this->calcularDistancia(
                                $request->latitud,
                                $request->longitud,
                                $ubicacion->latitude,
                                $ubicacion->longitude
                            );
                            $distanciaCalculada = $this->formatearDistancia($distancia);
                            $ubicacionTutor['distancia_km'] = round($distancia, 1);
                            $ubicacionTutor['distancia_texto'] = $distanciaCalculada;
                        } elseif ($ubicacionUsuario) {
                            // Desde ubicación del usuario autenticado
                            $distancia = $this->calcularDistancia(
                                $ubicacionUsuario['latitude'],
                                $ubicacionUsuario['longitude'],
                                $ubicacion->latitude,
                                $ubicacion->longitude
                            );
                            $distanciaCalculada = $this->formatearDistancia($distancia);
                            $ubicacionTutor['distancia_km'] = round($distancia, 1);
                            $ubicacionTutor['distancia_texto'] = $distanciaCalculada;
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
                        'distancia' => $distanciaCalculada ?? null,
                        'distancia_km' => $ubicacionTutor['distancia_km'] ?? null
                    ];
                    
                } catch (\Exception $e) {
                    Log::error('Error procesando oferta: ' . $e->getMessage());
                    return null;
                }
            })->filter()->values();

            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'count' => $ofertasFormateadas->count(),
                'filtros_aplicados' => [
                    'ubicacion' => $request->has('latitud') ? [
                        'nombre' => $request->ubicacion,
                        'latitud' => $request->latitud,
                        'longitud' => $request->longitud,
                        'radio_km' => $request->radio_km ?? 10
                    ] : null,
                    'otros_filtros' => $request->except(['latitud', 'longitud', 'radio_km', 'ubicacion'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener ofertas de adopción: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las ofertas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método específico para buscar ofertas por ubicación
     * (Puede ser usado para un endpoint separado)
     */
    public function buscarPorUbicacion(Request $request)
    {
        try {
            $request->validate([
                'latitud' => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
                'radio_km' => 'sometimes|numeric|min:1|max:200',
                'ciudad' => 'sometimes|string',
                'provincia' => 'sometimes|string'
            ]);
            
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual'
            ])
            ->where('estado_oferta', 'publicada');
            
            // Excluir ofertas del usuario autenticado si está logueado
            if (Auth::check()) {
                $usuario = Auth::user()->userable;
                $query->where('id_usuario_responsable', '!=', $usuario->id)
                    ->whereDoesntHave('mascota', function($q) use ($usuario) {
                        $q->where('usuario_id', Auth::id());
                    });
            }
            
            // Aplicar filtros de ubicación
            if ($request->has('ciudad') || $request->has('provincia')) {
                $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($request) {
                    if ($request->has('ciudad')) {
                        $q->where('city', 'like', '%' . $request->ciudad . '%');
                    }
                    if ($request->has('provincia')) {
                        $q->where('state', 'like', '%' . $request->provincia . '%');
                    }
                });
            } else {
                // Filtrar por radio de distancia
                $lat = $request->latitud;
                $lon = $request->longitud;
                $radio = $request->radio_km ?? 10;
                
                $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($lat, $lon, $radio) {
                    $q->whereRaw(
                        "ST_DWithin(
                            location::geography,
                            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                            ?
                        )",
                        [$lon, $lat, $radio * 1000]
                    );
                });
                
                // Ordenar por distancia
                $query->select('*')
                    ->addSelect(DB::raw(
                        "ST_Distance(
                            location::geography,
                            ST_SetSRID(ST_MakePoint($lon, $lat), 4326)::geography
                        ) / 1000 as distancia_km"
                    ))
                    ->orderBy('distancia_km', 'asc');
            }
            
            // Aplicar otros filtros si existen
            $filtrosController = new FiltrosMascotasController();
            $query = $filtrosController->aplicarFiltros($query, $request);
            
            $ofertas = $query->orderBy('created_at', 'desc')->get();
            
            // Formatear respuesta
            $ofertasFormateadas = $ofertas->map(function($oferta) use ($request) {
                $mascota = $oferta->mascota;
                
                $ubicacionTutor = null;
                $ubicacionTexto = 'Ubicación no disponible';
                
                if ($mascota->usuario && 
                    $mascota->usuario->user && 
                    $ubicacion = $mascota->usuario->user->ubicacionActual) {
                    
                    $ubicacionTutor = [
                        'latitude' => $ubicacion->latitude,
                        'longitude' => $ubicacion->longitude,
                        'city' => $ubicacion->city,
                        'state' => $ubicacion->state,
                        'country' => $ubicacion->country,
                    ];
                    
                    $parts = [];
                    if ($ubicacion->city) $parts[] = $ubicacion->city;
                    if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                        $parts[] = $ubicacion->state;
                    }
                    if ($ubicacion->country) $parts[] = $ubicacion->country;
                    $ubicacionTexto = implode(', ', $parts);
                    
                    // Calcular distancia si se proporcionaron coordenadas
                    if ($request->has('latitud') && $request->has('longitud')) {
                        $distancia = $this->calcularDistancia(
                            $request->latitud,
                            $request->longitud,
                            $ubicacion->latitude,
                            $ubicacion->longitude
                        );
                        $ubicacionTutor['distancia_km'] = round($distancia, 1);
                        $ubicacionTutor['distancia_texto'] = $this->formatearDistancia($distancia);
                    }
                }
                
                return [
                    'id_oferta' => $oferta->id_oferta,
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'especie' => $mascota->especie,
                        'foto_principal_url' => $mascota->fotos->first()?->url,
                        'ubicacion_texto' => $ubicacionTexto,
                        'ubicacion' => $ubicacionTutor,
                        'edad_formateada' => $mascota->edad_formateada,
                    ],
                    'distancia' => $ubicacionTutor['distancia_texto'] ?? null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'count' => $ofertasFormateadas->count(),
                'filtros' => $request->all()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en buscarPorUbicacion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar por ubicación'
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
            
            $usuarioModel = $usuario->userable;
            // Consulta base con ubicación
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual' // ✅ Añadir ubicación
            ])
            ->where('estado_oferta', 'publicada')
            ->where('id_usuario_responsable', '!=', $usuarioModel->id);
            
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
            Log::info('📌 📌 📌 EJECUTANDO obtenerOfertasProximidad() 📌 📌 📌');
            
            $user = Auth::user();
            $usuarioId = $user->id;
            
            // Verificar si el usuario tiene ubicación
            $ubicacionUsuario = $user->ubicacionActual;
            
            if (!$ubicacionUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Necesitas permitir el acceso a tu ubicación para ver mascotas cerca de ti.'
                ], 400);
            }
            
            Log::info('Usuario autenticado para proximidad:', [
                'usuario_id' => $usuarioId,
                'email' => $user->email
            ]);
            
            // ✅ CORRECCIÓN: Usar el método corregido que EXCLUYE las ofertas del usuario
            $ofertas = $this->obtenerOfertasConDistancia($ubicacionUsuario, $request, $usuarioId);
            
            // Ordenar por distancia
            $ofertas = $ofertas->sortBy('distancia_km')->values();
            
            Log::info('Ofertas por proximidad obtenidas:', [
                'total_ofertas' => $ofertas->count(),
                'usuario_excluido' => $usuarioId
            ]);
            
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

    private function obtenerOfertasConDistancia($ubicacionUsuario, $request, $usuarioId = null)
    {
         if (!$usuarioId) {
            // ✅ CORRECCIÓN: Obtener el ID del Usuario (no del User)
            $user = Auth::user();
            $usuarioId = $user->userable->id;
        }
        
        $lat = $ubicacionUsuario->latitude;
        $lon = $ubicacionUsuario->longitude;
        
        Log::info('Obteniendo ofertas con distancia - Usuario excluido:', [
            'user_id' => Auth::id(),
            'usuario_id' => $usuarioId,
            'ubicacion' => compact('lat', 'lon')
        ]);
        
        // ✅ CONSULTA CORREGIDA: Excluir ofertas del usuario autenticado
        $query = OfertaAdopcion::with([
            'mascota.fotos',
            'mascota.caracteristicas',
            'mascota.edadRelacion',
            'mascota.usuario.user.ubicacionActual'
        ])
        ->where('estado_oferta', 'publicada')
        // ✅ EXCLUSIÓN CRÍTICA: No mostrar ofertas del usuario
        ->where('id_usuario_responsable', '!=', $usuarioId)
        // ✅ EXCLUSIÓN ADICIONAL: Por si acaso (excluir mascotas donde el usuario sea el tutor)
        ->whereDoesntHave('mascota', function($q) use ($usuarioId) {
            $q->where('usuario_id', $usuarioId);
        });
        
        // Solo incluir ubicación si el usuario la tiene
        $query->whereHas('mascota.usuario.user.ubicacionActual');
        
        // Log de la consulta SQL
        Log::info('Consulta SQL para proximidad:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'usuario_excluido' => $usuarioId
        ]);
        
        return $query->get()
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
                
                // ✅ Agregar información de depuración
                $oferta->es_del_usuario = false; // Ya está excluido
                $oferta->id_usuario_responsable_debug = $oferta->id_usuario_responsable;
                $oferta->mascota_usuario_id_debug = $oferta->mascota->usuario_id;
                
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

    private function formatearUbicacionTexto($ubicacion)
    {
        $parts = [];
        if (!empty($ubicacion['city'])) $parts[] = $ubicacion['city'];
        if (!empty($ubicacion['state']) && $ubicacion['state'] !== $ubicacion['city']) {
            $parts[] = $ubicacion['state'];
        }
        if (!empty($ubicacion['country'])) $parts[] = $ubicacion['country'];
        
        return implode(', ', $parts);
    }

    /**
     * Obtener ofertas con jerarquía de ubicación
     */
    public function obtenerOfertasConJerarquiaUbicacion(Request $request)
    {
        Log::info('📌 EJECUTANDO obtenerOfertasConJerarquiaUbicacion() 📌');
        Log::info('Datos de request:', $request->all());
        
        try {
            $usuarioAutenticado = Auth::user();
            
            // Datos de ubicación seleccionada por el usuario
            $ubicacionSeleccionada = null;
            $ciudadSeleccionada = null;
            $provinciaSeleccionada = null;
            
            if ($request->has('latitud') && $request->has('longitud')) {
                $ubicacionSeleccionada = [
                    'latitud' => $request->latitud,
                    'longitud' => $request->longitud,
                    'nombre' => $request->ubicacion ?? 'Ubicación seleccionada',
                    'ciudad' => $request->ciudad ?? null,
                    'provincia' => $request->provincia ?? null
                ];
                
                $ciudadSeleccionada = $request->ciudad;
                $provinciaSeleccionada = $request->provincia;
                
                Log::info('Ubicación seleccionada:', $ubicacionSeleccionada);
            } else {
                Log::info('NO hay ubicación seleccionada en los filtros');
            }
            
            // Si NO hay ubicación seleccionada, usar la lógica normal de proximidad
            if (!$ubicacionSeleccionada) {
                Log::info('Usando lógica de proximidad normal (sin ubicación específica)');
                return $this->obtenerOfertasProximidad($request);
            }
            
            // Ubicación actual del usuario (para el último nivel de jerarquía)
            $ubicacionUsuario = $usuarioAutenticado->ubicacionActual ? [
                'latitude' => $usuarioAutenticado->ubicacionActual->latitude,
                'longitude' => $usuarioAutenticado->ubicacionActual->longitude,
                'city' => $usuarioAutenticado->ubicacionActual->city,
                'state' => $usuarioAutenticado->ubicacionActual->state,
                'country' => $usuarioAutenticado->ubicacionActual->country
            ] : null;
            
            Log::info('Ubicación usuario actual:', $ubicacionUsuario);
            
            // Obtener todas las ofertas (aplicando filtros excepto ubicación)
            $queryBase = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual'
            ])
            ->where('estado_oferta', 'publicada');
            
            // Excluir ofertas del usuario autenticado
            $usuario = $usuarioAutenticado->userable;
            $queryBase->where('id_usuario_responsable', '!=', $usuario->id)
                ->whereDoesntHave('mascota', function($q) use ($usuarioAutenticado) {
                    $q->where('usuario_id', $usuarioAutenticado->id);
                });
            
            // Aplicar filtros estándar (especie, sexo, edad) pero NO de ubicación
            $filtrosController = new FiltrosMascotasController();
            
            // Clonar request sin parámetros de ubicación para aplicar otros filtros
            $requestSinUbicacion = clone $request;
            $requestSinUbicacion->merge([
                'latitud' => null,
                'longitud' => null,
                'ciudad' => null,
                'provincia' => null,
                'radio_km' => null,
                'ubicacion' => null
            ]);
            
            $queryBase = $filtrosController->aplicarFiltros($queryBase, $requestSinUbicacion);
            
            // 1️⃣ NIVEL 1: Ubicación exacta (ciudad específica dentro de radio)
            Log::info('Obteniendo ofertas Nivel 1 - Ubicación exacta');
            $ofertasNivel1 = $this->obtenerOfertasPorUbicacionExacta(
                $queryBase,
                $ubicacionSeleccionada,
                $request->radio_km ?? 10
            );
            
            Log::info('Nivel 1 encontradas: ' . $ofertasNivel1->count());
            
            // 2️⃣ NIVEL 2: Provincia seleccionada (sin las del nivel 1)
            Log::info('Obteniendo ofertas Nivel 2 - Provincia seleccionada');
            $ofertasNivel2 = $this->obtenerOfertasPorProvincia(
                $queryBase,
                $provinciaSeleccionada,
                $ofertasNivel1->pluck('id_oferta')->toArray()
            );
            
            Log::info('Nivel 2 encontradas: ' . $ofertasNivel2->count());
            
            // 3️⃣ NIVEL 3: Provincias vecinas
            Log::info('Obteniendo ofertas Nivel 3 - Provincias vecinas');
            $provinciasVecinas = UbicacionHelper::getProvinciasVecinas($provinciaSeleccionada);
            $ofertasNivel3 = $this->obtenerOfertasPorProvinciasVecinas(
                $queryBase,
                $provinciasVecinas,
                array_merge(
                    $ofertasNivel1->pluck('id_oferta')->toArray(),
                    $ofertasNivel2->pluck('id_oferta')->toArray()
                )
            );
            
            Log::info('Nivel 3 encontradas: ' . $ofertasNivel3->count());
            Log::info('Provincias vecinas:', $provinciasVecinas);
            
            // 4️⃣ NIVEL 4: Resto del país ordenado por distancia a la ubicación seleccionada
            Log::info('Obteniendo ofertas Nivel 4 - Resto del país');
            $ofertasNivel4 = $this->obtenerOfertasRestoPaisOrdenadoPorDistancia(
                $queryBase,
                $ubicacionSeleccionada,
                array_merge(
                    $ofertasNivel1->pluck('id_oferta')->toArray(),
                    $ofertasNivel2->pluck('id_oferta')->toArray(),
                    $ofertasNivel3->pluck('id_oferta')->toArray()
                )
            );
            
            Log::info('Nivel 4 encontradas: ' . $ofertasNivel4->count());
            
            // 5️⃣ NIVEL 5: Cerca del usuario (si tiene ubicación y hay ofertas)
            Log::info('Obteniendo ofertas Nivel 5 - Cerca del usuario');
            $ofertasNivel5 = collect([]);
            if ($ubicacionUsuario && $request->has('incluir_cerca_usuario')) {
                $ofertasNivel5 = $this->obtenerOfertasCercaDelUsuario(
                    $queryBase,
                    $ubicacionUsuario,
                    array_merge(
                        $ofertasNivel1->pluck('id_oferta')->toArray(),
                        $ofertasNivel2->pluck('id_oferta')->toArray(),
                        $ofertasNivel3->pluck('id_oferta')->toArray(),
                        $ofertasNivel4->pluck('id_oferta')->toArray()
                    ),
                    $request->distancia_maxima_usuario ?? 50
                );
            }
            
            Log::info('Nivel 5 encontradas: ' . $ofertasNivel5->count());
            
            // Combinar resultados manteniendo el orden jerárquico
            $todasLasOfertas = $ofertasNivel1
                ->concat($ofertasNivel2)
                ->concat($ofertasNivel3)
                ->concat($ofertasNivel4)
                ->concat($ofertasNivel5);
            
            Log::info('Total ofertas combinadas: ' . $todasLasOfertas->count());
            
            // Formatear resultados
            $ofertasFormateadas = $this->formatearOfertasConJerarquia(
                $todasLasOfertas,
                $ubicacionSeleccionada,
                $ubicacionUsuario
            );
            
            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'count' => $todasLasOfertas->count(),
                'jerarquia' => [
                    'nivel1_ubicacion_exacta' => $ofertasNivel1->count(),
                    'nivel2_provincia_seleccionada' => $ofertasNivel2->count(),
                    'nivel3_provincias_vecinas' => $ofertasNivel3->count(),
                    'nivel4_resto_pais' => $ofertasNivel4->count(),
                    'nivel5_cerca_usuario' => $ofertasNivel5->count(),
                    'ubicacion_seleccionada' => $ubicacionSeleccionada,
                    'provincias_vecinas' => $provinciasVecinas
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en obtenerOfertasConJerarquiaUbicacion: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las ofertas con jerarquía de ubicación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ofertas por ubicación exacta (radio específico)
     */
    private function obtenerOfertasPorUbicacionExacta($queryBase, $ubicacion, $radioKm)
    {
        $lat = $ubicacion['latitud'];
        $lon = $ubicacion['longitud'];
        $radioMetros = $radioKm * 1000;
        
        $query = clone $queryBase;
        
        return $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($lat, $lon, $radioMetros) {
            $q->whereRaw(
                "ST_DWithin(
                    location::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                    ?
                )",
                [$lon, $lat, $radioMetros]
            );
        })
        ->get()
        ->each(function($oferta) {
            $oferta->nivel_jerarquia = 1;
            $oferta->jerarquia_label = 'Ubicación exacta';
        });
    }

    /**
     * Obtener ofertas por provincia (excluyendo las ya obtenidas)
     */
    private function obtenerOfertasPorProvincia($queryBase, $provincia, $idsExcluidos)
    {
        if (!$provincia) {
            return collect([]);
        }
        
        $query = clone $queryBase;
        
        if (!empty($idsExcluidos)) {
            $query->whereNotIn('id_oferta', $idsExcluidos);
        }
        
        return $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($provincia) {
            $q->where('state', 'like', '%' . $provincia . '%')
            ->orWhere('city', 'like', '%' . $provincia . '%');
        })
        ->get()
        ->each(function($oferta) {
            $oferta->nivel_jerarquia = 2;
            $oferta->jerarquia_label = 'Misma provincia';
        });
    }

    /**
     * Obtener ofertas por provincias vecinas
     */
    private function obtenerOfertasPorProvinciasVecinas($queryBase, $provinciasVecinas, $idsExcluidos)
    {
        if (empty($provinciasVecinas)) {
            return collect([]);
        }
        
        $query = clone $queryBase;
        
        if (!empty($idsExcluidos)) {
            $query->whereNotIn('id_oferta', $idsExcluidos);
        }
        
        return $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($provinciasVecinas) {
            $q->where(function($subq) use ($provinciasVecinas) {
                foreach ($provinciasVecinas as $provincia) {
                    $subq->orWhere('state', 'like', '%' . $provincia . '%');
                }
            });
        })
        ->get()
        ->each(function($oferta) {
            $oferta->nivel_jerarquia = 3;
            $oferta->jerarquia_label = 'Provincias vecinas';
        });
    }

    /**
     * Obtener resto del país ordenado por distancia
     */
    private function obtenerOfertasRestoPaisOrdenadoPorDistancia($queryBase, $ubicacion, $idsExcluidos)
    {
        $lat = $ubicacion['latitud'];
        $lon = $ubicacion['longitud'];
        
        $query = clone $queryBase;
        
        if (!empty($idsExcluidos)) {
            $query->whereNotIn('id_oferta', $idsExcluidos);
        }
        
        // Solo ofertas en Argentina
        $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) {
            $q->where('country_code', 'AR')
            ->orWhere('country', 'like', '%Argentina%');
        });
        
        // Calcular distancia y ordenar
        return $query->get()
            ->map(function($oferta) use ($lat, $lon) {
                $ubicacionTutor = $oferta->mascota->usuario->user->ubicacionActual;
                
                if ($ubicacionTutor) {
                    $distancia = $this->calcularDistancia(
                        $lat, $lon,
                        $ubicacionTutor->latitude,
                        $ubicacionTutor->longitude
                    );
                    $oferta->distancia_a_seleccionada = $distancia;
                } else {
                    $oferta->distancia_a_seleccionada = null;
                }
                
                $oferta->nivel_jerarquia = 4;
                $oferta->jerarquia_label = 'Resto del país';
                
                return $oferta;
            })
            ->filter(function($oferta) {
                return $oferta->distancia_a_seleccionada !== null;
            })
            ->sortBy('distancia_a_seleccionada')
            ->values();
    }

    /**
     * Obtener ofertas cerca del usuario (último nivel)
     */
    private function obtenerOfertasCercaDelUsuario($queryBase, $ubicacionUsuario, $idsExcluidos, $distanciaMaxima)
    {
        $lat = $ubicacionUsuario['latitude'];
        $lon = $ubicacionUsuario['longitude'];
        $radioMetros = $distanciaMaxima * 1000;
        
        $query = clone $queryBase;
        
        if (!empty($idsExcluidos)) {
            $query->whereNotIn('id_oferta', $idsExcluidos);
        }
        
        return $query->whereHas('mascota.usuario.user.ubicacionActual', function($q) use ($lat, $lon, $radioMetros) {
            $q->whereRaw(
                "ST_DWithin(
                    location::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                    ?
                )",
                [$lon, $lat, $radioMetros]
            );
        })
        ->get()
        ->each(function($oferta) {
            $oferta->nivel_jerarquia = 5;
            $oferta->jerarquia_label = 'Cerca de ti';
        });
    }

    /**
     * Formatear ofertas incluyendo información de jerarquía
     */
    private function formatearOfertasConJerarquia($ofertas, $ubicacionSeleccionada, $ubicacionUsuario)
    {
        return $ofertas->map(function($oferta) use ($ubicacionSeleccionada, $ubicacionUsuario) {
            try {
                $mascota = $oferta->mascota;
                
                if (!$mascota) {
                    return null;
                }
                
                // Obtener ubicación del tutor
                $ubicacionTexto = 'Ubicación no disponible';
                $ubicacionTutor = null;
                
                if ($mascota->usuario && 
                    $mascota->usuario->user && 
                    $ubicacion = $mascota->usuario->user->ubicacionActual) {
                    
                    $ubicacionTutor = [
                        'latitude' => $ubicacion->latitude,
                        'longitude' => $ubicacion->longitude,
                        'city' => $ubicacion->city,
                        'state' => $ubicacion->state,
                        'country' => $ubicacion->country,
                        'country_code' => $ubicacion->country_code,
                    ];
                    
                    // Formatear texto de ubicación
                    $parts = [];
                    if ($ubicacion->city) $parts[] = $ubicacion->city;
                    if ($ubicacion->state && $ubicacion->state !== $ubicacion->city) {
                        $parts[] = $ubicacion->state;
                    }
                    if ($ubicacion->country) $parts[] = $ubicacion->country;
                    $ubicacionTexto = implode(', ', $parts);
                    
                    // Calcular distancia desde ubicación seleccionada
                    if ($ubicacionSeleccionada) {
                        $distanciaDesdeSeleccionada = $this->calcularDistancia(
                            $ubicacionSeleccionada['latitud'],
                            $ubicacionSeleccionada['longitud'],
                            $ubicacion->latitude,
                            $ubicacion->longitude
                        );
                        $ubicacionTutor['distancia_desde_seleccionada_km'] = round($distanciaDesdeSeleccionada, 1);
                        $ubicacionTutor['distancia_desde_seleccionada_texto'] = $this->formatearDistancia($distanciaDesdeSeleccionada);
                    }
                    
                    // Calcular distancia desde usuario (si aplica)
                    if ($ubicacionUsuario && $oferta->nivel_jerarquia == 5) {
                        $distanciaDesdeUsuario = $this->calcularDistancia(
                            $ubicacionUsuario['latitude'],
                            $ubicacionUsuario['longitude'],
                            $ubicacion->latitude,
                            $ubicacion->longitude
                        );
                        $ubicacionTutor['distancia_desde_usuario_km'] = round($distanciaDesdeUsuario, 1);
                        $ubicacionTutor['distancia_desde_usuario_texto'] = $this->formatearDistancia($distanciaDesdeUsuario);
                    }
                }
                
                // Obtener foto principal
                $fotoPrincipal = $mascota->fotos->first();
                $fotoPrincipalUrl = $fotoPrincipal ? $fotoPrincipal->url : null;
                
                // Determinar rango etario
                $rangoEtario = 'Adulto';
                if ($mascota->edadRelacion && $mascota->edadRelacion->dias !== null) {
                    $rangoEtario = FiltrosMascotasController::determinarRangoEtario(
                        $mascota->especie,
                        $mascota->edadRelacion->dias
                    );
                }
                
                // Preparar fotos
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
                    'jerarquia' => [
                        'nivel' => $oferta->nivel_jerarquia ?? 0,
                        'label' => $oferta->jerarquia_label ?? 'Sin clasificar',
                        'distancia_desde_seleccionada' => $ubicacionTutor['distancia_desde_seleccionada_texto'] ?? null,
                        'distancia_desde_usuario' => $ubicacionTutor['distancia_desde_usuario_texto'] ?? null
                    ],
                    'created_at' => $oferta->created_at
                ];
                
            } catch (\Exception $e) {
                Log::error('Error procesando oferta en formatearOfertasConJerarquia: ' . $e->getMessage());
                return null;
            }
        })->filter()->values();
    }
}