<?php

namespace App\Http\Controllers;

use App\Models\OfertaAdopcion;
use App\Models\Mascota;
use App\Models\MascotaFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfertaAdopcionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $usuarioId = $user->userable->id;
            
            $ofertas = OfertaAdopcion::with(['mascota' => function($query) {
                $query->select('id', 'nombre', 'especie');
            }])
            ->where('id_usuario_responsable', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($oferta) {
                $oferta->mascota->foto_url = $oferta->mascota->foto_principal_url;
                return $oferta;
            });
            
            return response()->json([
                'success' => true,
                'data' => $ofertas
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las ofertas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            $usuarioId = $user->userable->id; 
            
            Log::info('Creando oferta de adopción', [
                'usuario_id' => $user->id,
                'datos_recibidos' => $request->all()
            ]);
            
            $validator = Validator::make($request->all(), [
                'mascotaId' => 'required|integer|exists:mascotas,id',
                'permisos' => 'required|array',
                'permisos.compartirHistorialMedico' => 'required|boolean',
                'permisos.compartirMediosContacto' => 'required|boolean',
            ]);
            
            if ($validator->fails()) {
                Log::error('Validación fallida', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $mascota = Mascota::with(['fotos' => function($query) {
                $query->where('es_principal', true)->first();
            }])
            ->where('id', $request->mascotaId)
            ->where('usuario_id', $usuarioId)
            ->first();
            
            if (!$mascota) {
                Log::error('Mascota no pertenece al usuario', [
                    'mascota_id' => $request->mascotaId,
                    'usuario_id' => $usuarioId,
                    'user_id' => $user->id,
                    'userable_type' => $user->userable_type,
                    'userable_id' => $user->userable_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'La mascota no existe o no pertenece al usuario'
                ], 404);
            }
            
            $ofertaExistente = OfertaAdopcion::where('id_mascota', $request->mascotaId)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->first();
            
            if ($ofertaExistente) {
                Log::error('Ya existe oferta activa', ['mascota_id' => $request->mascotaId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una oferta activa para esta mascota'
                ], 409);
            }
            
            $oferta = OfertaAdopcion::create([
                'id_mascota' => $request->mascotaId,
                'id_usuario_responsable' => $usuarioId,
                'estado_oferta' => 'publicada',
                'permiso_historial_medico' => $request->permisos['compartirHistorialMedico'],
                'permiso_contacto_tutor' => $request->permisos['compartirMediosContacto'],
            ]);
            
            Log::info('Oferta creada exitosamente', ['oferta_id' => $oferta->id_oferta]);
            
            DB::commit();
            
            $responseData = [
                'success' => true,
                'message' => 'Oferta de adopción creada exitosamente',
                'data' => [
                    'id_oferta' => $oferta->id_oferta,
                    'id_mascota' => $oferta->id_mascota,
                    'estado_oferta' => $oferta->estado_oferta,
                    'mascota_nombre' => $mascota->nombre,
                    'mascota_foto_url' => $mascota->foto_principal_url,
                    'mascota_especie' => $mascota->especie,
                    'permisos' => [
                        'historial_medico' => $oferta->permiso_historial_medico,
                        'contacto_tutor' => $oferta->permiso_contacto_tutor
                    ],
                    'created_at' => $oferta->created_at
                ]
            ];
            
            return response()->json($responseData, 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear oferta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la oferta de adopción',
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
            Log::info('OfertaAdopcionController@show llamado', [
                'id_recibido' => $id,
                'es_numerico' => is_numeric($id),
                'url_completa' => request()->fullUrl()
            ]);
            
            if (!is_numeric($id)) {
                Log::warning('ID no numérico recibido en show', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'ID inválido'
                ], 400);
            }

            // ✅ Cargar TODAS las relaciones necesarias CORRECTAMENTE
            $oferta = OfertaAdopcion::with([
                'mascota.fotos',
                'mascota.caracteristicas',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual',
                'usuarioResponsable'
            ])
            ->where('id_oferta', $id)
            ->where('estado_oferta', 'publicada')
            ->first();
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oferta no encontrada o no está disponible'
                ], 404);
            }
            
            // ✅ Obtener ubicación del tutor con logs de debug
            $ubicacionTutor = null;
            $ubicacionTexto = 'Ubicación no disponible';
            
            Log::debug('Verificando relaciones de ubicación', [
                'tiene_mascota' => !is_null($oferta->mascota),
                'tiene_usuario' => !is_null($oferta->mascota->usuario),
                'tiene_user' => $oferta->mascota->usuario ? !is_null($oferta->mascota->usuario->user) : false,
                'tiene_ubicacion' => $oferta->mascota->usuario && $oferta->mascota->usuario->user ? 
                    !is_null($oferta->mascota->usuario->user->ubicacionActual) : false
            ]);
            
            if ($oferta->mascota->usuario && 
                $oferta->mascota->usuario->user && 
                $oferta->mascota->usuario->user->ubicacionActual) {
                
                $ubicacion = $oferta->mascota->usuario->user->ubicacionActual;
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
                
                $ubicacionTexto = $this->formatearUbicacionTexto($ubicacionTutor);
                Log::debug('Ubicación obtenida', ['ubicacion' => $ubicacionTutor, 'texto' => $ubicacionTexto]);
            }
            
            $fotos = $oferta->mascota->fotos->map(function($foto) {
                return [
                    'id' => $foto->id,
                    'url' => $foto->url,
                    'es_principal' => $foto->es_principal,
                    'ruta_foto' => $foto->ruta_foto
                ];
            });
            
            $datosMascota = [
                'id' => $oferta->mascota->id,
                'nombre' => $oferta->mascota->nombre,
                'especie' => $oferta->mascota->especie,
                'sexo' => $oferta->mascota->sexo,
                'castrado' => $oferta->mascota->castrado,
                'fecha_nacimiento' => $oferta->mascota->fecha_nacimiento,
                'usuario_id' => $oferta->mascota->usuario_id,
                'caracteristicas' => is_string($oferta->mascota->caracteristicas ?? '') 
                    ? json_decode($oferta->mascota->caracteristicas, true) 
                    : ($oferta->mascota->caracteristicas ?? []),
                'fotos' => $fotos,
                'foto_principal_url' => $oferta->mascota->foto_principal_url,
                'edad' => $oferta->mascota->edadRelacion ? [
                    'dias' => $oferta->mascota->edadRelacion->dias,
                    'meses' => $oferta->mascota->edadRelacion->meses,
                    'años' => $oferta->mascota->edadRelacion->años,
                    'edad_formateada' => $oferta->mascota->edadRelacion->edad_formateada
                ] : null,
                'edad_formateada' => $oferta->mascota->edad_formateada,
                // ✅ INCLUIR UBICACIÓN CORRECTAMENTE
                'ubicacion' => $ubicacionTutor,
                'ubicacion_texto' => $ubicacionTexto
            ];
            
            $usuarioResponsable = [
                'id' => $oferta->usuarioResponsable->id ?? null,
                'nombre' => $oferta->usuarioResponsable->nombre ?? 'Usuario',
            ];
            
            Log::info('Oferta cargada exitosamente', [
                'oferta_id' => $oferta->id_oferta,
                'mascota_id' => $oferta->mascota->id,
                'fotos_count' => $fotos->count(),
                'tiene_ubicacion' => !is_null($ubicacionTutor),
                'ubicacion_incluida' => isset($datosMascota['ubicacion'])
            ]);
            
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
                    'mascota' => $datosMascota,
                    'usuario_responsable' => $usuarioResponsable
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en OfertaAdopcionController@show: ' . $e->getMessage());
            Log::error('Trace:', ['trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la oferta',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            $oferta = OfertaAdopcion::where('id_oferta', $id)
                ->where('id_usuario_responsable', $user->id)
                ->first();
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oferta no encontrada'
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'estado_oferta' => 'sometimes|in:publicada,pausada,en_proceso,cerrada,cancelada',
                'permiso_historial_medico' => 'sometimes|boolean',
                'permiso_contacto_tutor' => 'sometimes|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $oferta->update($request->only([
                'estado_oferta',
                'permiso_historial_medico',
                'permiso_contacto_tutor'
            ]));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Oferta actualizada exitosamente',
                'data' => $oferta
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la oferta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return $this->cancelar($id);
    }

    /**
     * Cancelar una oferta de adopción
     */
    public function cancelar($id)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            $oferta = OfertaAdopcion::where('id_oferta', $id)
                ->where('id_usuario_responsable', $user->id)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->first();
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una oferta activa'
                ], 404);
            }
            
            $oferta->update([
                'estado_oferta' => 'cancelada'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Oferta cancelada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la oferta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar oferta por ID de mascota (para compatibilidad con frontend existente)
     */
    public function cancelarPorMascota($mascotaId)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            $oferta = OfertaAdopcion::where('id_mascota', $mascotaId)
                ->where('id_usuario_responsable', $user->id)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->first();
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una oferta activa para esta mascota'
                ], 404);
            }
            
            $oferta->update([
                'estado_oferta' => 'cancelada'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Oferta cancelada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la oferta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener mascotas disponibles para adopción (que no están en ofertas activas)
     */
    public function getMascotasDisponibles()
    {
        try {
            $user = Auth::user();
            $usuarioId = $user->userable->id;
            
            Log::info('Obteniendo mascotas disponibles para usuario', [
                'usuario_id' => $user->id,
                'userable_type' => $user->userable_type ?? 'N/A',
                'userable_id' => $user->userable_id ?? 'N/A'
            ]);
            
            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información del usuario'
                ], 404);
            }
            
            $mascotasConOfertaActiva = OfertaAdopcion::where('id_usuario_responsable', $usuarioId)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->pluck('id_mascota')
                ->toArray();
            
            Log::info('Mascotas con oferta activa', ['ids' => $mascotasConOfertaActiva]);
            
            $mascotas = Mascota::where('usuario_id', $usuarioId)
                ->whereNotIn('id', $mascotasConOfertaActiva)
                ->whereNull('deleted_at')
                ->get()
                ->map(function($mascota) {
                    $caracteristicas = is_string($mascota->caracteristicas) 
                        ? json_decode($mascota->caracteristicas, true) 
                        : $mascota->caracteristicas;
                    
                    return [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'foto_url' => $mascota->foto_principal_url,
                        'especie' => $mascota->especie,
                        'edad_formateada' => $mascota->edad_formateada,
                        'caracteristicas' => $caracteristicas
                    ];
                });
            
            Log::info('Mascotas disponibles encontradas', [
                'count' => $mascotas->count(),
                'mascotas' => $mascotas->toArray()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $mascotas
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener mascotas disponibles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'query' => $e instanceof \Illuminate\Database\QueryException ? $e->getSql() : null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mascotas disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ofertas en adopción del usuario (para la vista de gestión)
     */
    public function getOfertasUsuario()
    {
        try {
            $user = Auth::user();
            $usuarioId = $user->userable->id;
            
            $ofertas = OfertaAdopcion::with(['mascota' => function($query) {
                $query->select('id', 'nombre', 'especie');
            }])
            ->where('id_usuario_responsable', $usuarioId)
            ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
            ->get()
            ->map(function($oferta) {
                return [
                    'id' => $oferta->mascota->id,
                    'nombre' => $oferta->mascota->nombre,
                    'foto' => $oferta->mascota->foto_principal_url,
                    'especie' => $oferta->mascota->especie,
                    'estadoAdopcion' => $oferta->estado_oferta,
                    'oferta_id' => $oferta->id_oferta
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $ofertas
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mascotas en adopción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las ofertas de adopción disponibles (excepto las del usuario autenticado)
     * CON FILTROS Y UBICACIÓN
     */
    public function getOfertasDisponibles(Request $request)
    {
        Log::info('=== INICIO getOfertasDisponibles CON FILTROS Y UBICACIÓN ===');
        Log::info('Request data:', $request->all());
        
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('Usuario no autenticado');
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            Log::info('Usuario autenticado:', [
                'id' => $user->id, 
                'email' => $user->email,
                'userable_type' => $user->userable_type ?? 'N/A',
                'userable_id' => $user->userable_id ?? 'N/A'
            ]);
            
            // ✅ Obtener la ubicación actual del usuario autenticado
            $ubicacionUsuario = null;
            $userLocation = $user->ubicacionActual;
            if ($userLocation) {
                $ubicacionUsuario = [
                    'latitude' => $userLocation->latitude,
                    'longitude' => $userLocation->longitude,
                    'city' => $userLocation->city,
                    'state' => $userLocation->state,
                    'country' => $userLocation->country
                ];
            }
            
            Log::info('Ubicación del usuario autenticado:', $ubicacionUsuario);
            
            // ✅ Consulta base con TODAS las relaciones necesarias CORREGIDA
            $query = OfertaAdopcion::with([
                'mascota.fotos',
                'mascota.caracteristicas',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual',
                'usuarioResponsable:id,nombre'
            ])
            ->where('estado_oferta', 'publicada');
            
            // Excluir ofertas del usuario autenticado
            $query->where('id_usuario_responsable', '!=', $user->id);
            
            Log::info('Consulta base creada');
            
            // ✅ APLICAR FILTROS usando el controlador de filtros
            $filtrosController = new FiltrosMascotasController();
            $query = $filtrosController->aplicarFiltros($query, $request);
            
            // Filtrar por distancia si el usuario tiene ubicación
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
            
            Log::info('Consulta después de filtros aplicados');
            
            $ofertas = $query->orderBy('created_at', 'desc')->get();
            Log::info('Total ofertas encontradas después de filtros: ' . $ofertas->count());
            
            // ✅ Procesar cada oferta para incluir ubicación CORRECTAMENTE
            $ofertasFormateadas = $ofertas->map(function($oferta) use ($ubicacionUsuario) {
                try {
                    if (!$oferta->mascota) {
                        Log::warning('Oferta sin mascota: ' . $oferta->id_oferta);
                        return null;
                    }
                    
                    $mascota = $oferta->mascota;
                    
                    // ✅ Obtener ubicación del tutor con logs detallados
                    $ubicacionTutor = null;
                    $ubicacionTexto = 'Ubicación no disponible';
                    
                    Log::debug('Procesando ubicación para mascota ' . $mascota->id, [
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
                        
                        $ubicacionTutor = [
                            'latitude' => $ubicacion->latitude,
                            'longitude' => $ubicacion->longitude,
                            'city' => $ubicacion->city,
                            'state' => $ubicacion->state,
                            'country' => $ubicacion->country,
                            'country_code' => $ubicacion->country_code,
                            'accuracy' => $ubicacion->accuracy
                        ];
                        
                        // Formatear texto de ubicación
                        $ubicacionTexto = $this->formatearUbicacionTexto($ubicacionTutor);
                        
                        // Calcular distancia si el usuario tiene ubicación
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
                        
                        Log::debug('Ubicación obtenida para tutor', [
                            'ubicacion' => $ubicacionTutor,
                            'texto' => $ubicacionTexto
                        ]);
                    } else {
                        Log::debug('No se pudo obtener ubicación para mascota ' . $mascota->id);
                    }
                    
                    // Obtener foto principal
                    $fotoPrincipal = $mascota->fotos->first();
                    $fotoPrincipalUrl = $fotoPrincipal ? $fotoPrincipal->url : null;
                    
                    if (!$fotoPrincipalUrl && $mascota->fotos->isNotEmpty()) {
                        $fotoPrincipalUrl = $mascota->fotos->first()->url;
                    }
                    
                    // Determinar rango etario
                    $rangoEtario = 'Adulto';
                    if ($mascota->edadRelacion && $mascota->edadRelacion->dias !== null) {
                        $rangoEtario = FiltrosMascotasController::determinarRangoEtario(
                            $mascota->especie,
                            $mascota->edadRelacion->dias
                        );
                    }
                    
                    // ✅ Asegurar que la ubicación se incluya en el array de la mascota
                    $datosMascota = [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre ?? 'Sin nombre',
                        'especie' => $mascota->especie ?? 'Desconocido',
                        'sexo' => $mascota->sexo ?? 'Desconocido',
                        'castrado' => $mascota->castrado,
                        'edad_formateada' => $mascota->edad_formateada ?? 'Edad no especificada',
                        'rango_etario' => $rangoEtario,
                        'caracteristicas' => is_string($mascota->caracteristicas ?? '') 
                            ? json_decode($mascota->caracteristicas, true) 
                            : ($mascota->caracteristicas ?? []),
                        'foto_principal_url' => $fotoPrincipalUrl,
                        // ✅ INCLUIR UBICACIÓN CORRECTAMENTE - esto es lo más importante
                        'ubicacion' => $ubicacionTutor,
                        'ubicacion_texto' => $ubicacionTexto,
                        'fotos' => $mascota->fotos->map(function($foto) {
                            return [
                                'id' => $foto->id,
                                'url' => $foto->url,
                                'es_principal' => $foto->es_principal,
                                'ruta_foto' => $foto->ruta_foto
                            ];
                        })->toArray(),
                        'usuario_id' => $mascota->usuario_id,
                    ];
                    
                    Log::debug('Datos de mascota preparados', [
                        'mascota_id' => $mascota->id,
                        'incluye_ubicacion' => isset($datosMascota['ubicacion']),
                        'ubicacion_no_nula' => !is_null($datosMascota['ubicacion']),
                        'ubicacion_texto' => $datosMascota['ubicacion_texto']
                    ]);
                    
                    return [
                        'id_oferta' => $oferta->id_oferta,
                        'mascota' => $datosMascota,
                        'usuario_responsable' => [
                            'id' => $oferta->usuarioResponsable->id ?? null,
                            'nombre' => $oferta->usuarioResponsable->nombre ?? 'Usuario',
                        ],
                        'permisos' => [
                            'historial_medico' => $oferta->permiso_historial_medico ?? false,
                            'contacto_tutor' => $oferta->permiso_contacto_tutor ?? false,
                        ],
                        'estado_oferta' => $oferta->estado_oferta,
                        'created_at' => $oferta->created_at ? $oferta->created_at->format('d/m/Y H:i') : 'Fecha no disponible',
                        'distancia' => $ubicacionTutor['distancia_texto'] ?? null
                    ];
                } catch (\Exception $e) {
                    Log::error('Error procesando oferta ' . ($oferta->id_oferta ?? 'unknown') . ': ' . $e->getMessage());
                    Log::error('Trace:', ['trace' => $e->getTraceAsString()]);
                    return null;
                }
            })->filter()->values();
            
            Log::info('Ofertas formateadas después de filtros: ' . $ofertasFormateadas->count());
            
            // ✅ Verificar que al menos una oferta tenga ubicación
            $ofertasConUbicacion = $ofertasFormateadas->filter(function($oferta) {
                return !is_null($oferta['mascota']['ubicacion']);
            })->count();
            
            Log::info('Ofertas con ubicación disponible: ' . $ofertasConUbicacion);
            
            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'total' => $ofertasFormateadas->count(),
                'ubicacion_usuario' => $ubicacionUsuario,
                'debug' => [
                    'user_id' => $user->id,
                    'filters_applied' => $request->all(),
                    'ofertas_con_ubicacion' => $ofertasConUbicacion,
                    'total_ofertas' => $ofertasFormateadas->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('=== ERROR CRÍTICO en getOfertasDisponibles ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Traza completa:', ['trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno',
                'debug' => env('APP_DEBUG') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
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
     * Formatear texto de ubicación
     */
    private function formatearUbicacionTexto($ubicacion)
    {
        if (!$ubicacion) {
            return 'Ubicación no disponible';
        }
        
        $parts = [];
        if (!empty($ubicacion['city'])) {
            $parts[] = $ubicacion['city'];
        }
        if (!empty($ubicacion['state']) && $ubicacion['state'] !== $ubicacion['city']) {
            $parts[] = $ubicacion['state'];
        }
        if (!empty($ubicacion['country'])) {
            $parts[] = $ubicacion['country'];
        }
        
        return implode(', ', $parts) ?: 'Ubicación no disponible';
    }

    /**
     * Obtener oferta por ID de mascota
     */
    public function getOfertaPorMascota($mascotaId)
    {
        try {
            $user = Auth::user();
            
            $mascota = Mascota::where('id', $mascotaId)
                ->where('usuario_id', $user->id)
                ->first();
            
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada o no autorizado'
                ], 404);
            }
            
            $oferta = OfertaAdopcion::with([
                'mascota' => function($query) {
                    $query->with(['fotos', 'caracteristicas', 'edadRelacion']);
                }
            ])
            ->where('id_mascota', $mascotaId)
            ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
            ->first();
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay oferta activa para esta mascota'
                ], 404);
            }
            
            $mascotaData = [
                'id' => $oferta->mascota->id,
                'nombre' => $oferta->mascota->nombre,
                'especie' => $oferta->mascota->especie,
                'raza' => $oferta->mascota->raza,
                'sexo' => $oferta->mascota->sexo,
                'castrado' => $oferta->mascota->castrado,
                'edad_formateada' => $oferta->mascota->edad_formateada,
                'foto_principal_url' => $oferta->mascota->foto_principal_url,
                'caracteristicas' => is_string($oferta->mascota->caracteristicas ?? '') 
                    ? json_decode($oferta->mascota->caracteristicas, true) 
                    : ($oferta->mascota->caracteristicas ?? []),
                'fotos' => $oferta->mascota->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url ?? asset('storage/' . $foto->ruta_foto),
                        'es_principal' => $foto->es_principal,
                        'ruta_foto' => $foto->ruta_foto
                    ];
                })
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id_oferta' => $oferta->id_oferta,
                    'estado_oferta' => $oferta->estado_oferta,
                    'permiso_historial_medico' => $oferta->permiso_historial_medico,
                    'permiso_contacto_tutor' => $oferta->permiso_contacto_tutor,
                    'created_at' => $oferta->created_at,
                    'mascota' => $mascotaData
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener oferta por mascota:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la oferta',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}