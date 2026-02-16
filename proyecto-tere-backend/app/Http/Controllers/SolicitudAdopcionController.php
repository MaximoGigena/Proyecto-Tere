<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAdopcion;
use App\Models\OfertaAdopcion;
use App\Models\Mascota;
use App\Models\Chat;
use App\Models\User;
use App\Models\Usuario;
use App\Models\HistorialTransferenciaMascota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SolicitudAdopcionController extends Controller
{
    /**
     * Crear una nueva solicitud de adopción
     */
    public function store(Request $request)
    {
        try {
            Log::info('=== INICIO store SolicitudAdopcion ===');
            Log::info('Datos recibidos:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'idMascota' => [
                    'required',
                    'integer',
                    'exists:mascotas,id',
                    // ✅ CORRECCIÓN: Verificar que la mascota tenga una oferta de adopción ACTIVA
                    function ($attribute, $value, $fail) {
                        $mascota = Mascota::find($value);
                        
                        if (!$mascota) {
                            $fail('La mascota no existe.');
                            return;
                        }
                        
                        // Verificar si existe una oferta de adopción ACTIVA para esta mascota
                        $ofertaActiva = OfertaAdopcion::where('id_mascota', $value)
                            ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                            ->exists();
                        
                        if (!$ofertaActiva) {
                            $fail('La mascota no tiene una oferta de adopción activa.');
                        }
                    },
                    // Validar que no exista una solicitud activa del mismo usuario
                    function ($attribute, $value, $fail) {
                        $userId = Auth::id();
                        $exists = SolicitudAdopcion::where('idUsuarioSolicitante', $userId)
                            ->where('idMascota', $value)
                            ->whereIn('estadoSolicitud', ['pendiente', 'aprobada'])
                            ->exists();
                        
                        if ($exists) {
                            $fail('Ya tienes una solicitud activa para esta mascota.');
                        }
                    }
                ],
                'aceptóTerminos' => ['required', 'boolean', 'accepted']
            ]);

            if ($validator->fails()) {
                Log::error('Validación fallida:', ['errors' => $validator->errors()->toArray()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener usuario autenticado
            $user = Auth::user();

            // Crear la solicitud
            $solicitud = SolicitudAdopcion::create([
                'idUsuarioSolicitante' => $user->id,
                'idMascota' => $request->idMascota,
                'estadoSolicitud' => 'pendiente',
                'aceptóTerminos' => true,
                'fechaSolicitud' => now()
            ]);

            Log::info('Solicitud creada exitosamente:', ['solicitud_id' => $solicitud->idSolicitud]);

            // Cargar relaciones para la respuesta
            $solicitud->load(['mascota', 'usuario']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de adopción creada exitosamente',
                'data' => [
                    'solicitud' => $solicitud,
                    'mascota' => $solicitud->mascota,
                    'usuario' => $solicitud->usuario->only(['id', 'name', 'email'])
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear la solicitud:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener solicitudes del usuario autenticado
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            $solicitudes = SolicitudAdopcion::with(['mascota', 'mascota.fotos'])
                ->where('idUsuarioSolicitante', $user->id)
                ->orderBy('fechaSolicitud', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'solicitudes' => $solicitudes,
                    'total' => $solicitudes->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una solicitud
     */
    public function cancelar($id)
    {
        try {
            $solicitud = SolicitudAdopcion::findOrFail($id);
            
            // Verificar que pertenezca al usuario
            if ($solicitud->idUsuarioSolicitante !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para cancelar esta solicitud'
                ], 403);
            }

            // Verificar que pueda ser cancelada
            if (!$solicitud->puedeCancelar()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud no puede ser cancelada'
                ], 400);
            }

            $solicitud->cancelar();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud cancelada exitosamente',
                'data' => $solicitud
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si el usuario tiene solicitud activa para una mascota
     */
    public function verificarSolicitud($idMascota)
    {
        try {
            $user = Auth::user();
            
            $solicitud = SolicitudAdopcion::where('idUsuarioSolicitante', $user->id)
                ->where('idMascota', $idMascota)
                ->whereIn('estadoSolicitud', ['pendiente', 'aprobada'])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'tieneSolicitud' => !is_null($solicitud),
                    'solicitud' => $solicitud
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener solicitudes RECIBIDAS (dirigidas al usuario autenticado)
     */
    public function solicitudesRecibidas()
    {
        try {
            $user = Auth::user();
            
            Log::info('=== INICIO solicitudesRecibidas ===');
            Log::info('Usuario: ' . $user->id);
            Log::info('Usuario userable ID: ' . ($user->userable->id ?? 'N/A'));
            
            // Obtener el ID del Usuario (no del User)
            $usuarioId = $user->userable->id;
            
            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene perfil completo'
                ], 400);
            }
            
            // Obtener todas las mascotas del usuario
            $mascotasIds = Mascota::where('usuario_id', $usuarioId)
                ->pluck('id')
                ->toArray();
                
            Log::info('Mascotas del usuario:', ['mascotas_ids' => $mascotasIds]);
            
            // Obtener ofertas activas para estas mascotas
            $ofertasActivasIds = OfertaAdopcion::whereIn('id_mascota', $mascotasIds)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->pluck('id_mascota')
                ->toArray();
                
            Log::info('Ofertas activas para mascotas:', ['mascotas_con_oferta' => $ofertasActivasIds]);
            
            // Obtener solicitudes para mascotas que tienen ofertas activas
            $solicitudes = SolicitudAdopcion::with([
                'mascota' => function($query) {
                    $query->with('fotos');
                },
                'usuarioSolicitante.userable' => function($query) {
                    // Solo cargar userable si existe
                    $query->when(true, function($q) {
                        // Si es Usuario, cargar fotos
                        if (method_exists($q->getModel(), 'fotos')) {
                            $q->with('fotos');
                        }
                    });
                }
            ])
            ->whereIn('idMascota', $ofertasActivasIds) // Solo mascotas con ofertas activas
            ->where('estadoSolicitud', 'pendiente')
            ->orderBy('fechaSolicitud', 'desc')
            ->get();

            Log::info('Solicitudes encontradas: ' . $solicitudes->count());
            
            if ($solicitudes->count() > 0) {
                foreach ($solicitudes as $solicitud) {
                    Log::info('Solicitud ID: ' . $solicitud->idSolicitud . 
                            ' | Mascota ID: ' . $solicitud->idMascota . 
                            ' | Mascota Nombre: ' . $solicitud->mascota->nombre . 
                            ' | Usuario Solicitante: ' . $solicitud->idUsuarioSolicitante .
                            ' | Tiene Oferta Activa: ' . (in_array($solicitud->idMascota, $ofertasActivasIds) ? 'Sí' : 'No'));
                }
            } else {
                Log::warning('No se encontraron solicitudes pendientes para las ofertas activas');
                
                // Depuración adicional: Verificar si hay mascotas sin solicitudes
                $mascotasSinSolicitudes = array_diff($ofertasActivasIds, 
                    SolicitudAdopcion::whereIn('idMascota', $ofertasActivasIds)
                        ->pluck('idMascota')
                        ->toArray()
                );
                Log::info('Mascotas con ofertas activas pero sin solicitudes:', ['mascotas' => $mascotasSinSolicitudes]);
            }

            // Formatear la respuesta para el frontend
            $solicitudesFormateadas = $solicitudes->map(function($solicitud) {
                $userSolicitante = $solicitud->usuarioSolicitante;
                $usuarioDetalles = $userSolicitante->userable ?? null;
                
                // Obtener nombre del usuario
                $nombre = 'Usuario';
                $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
                
                if ($usuarioDetalles instanceof \App\Models\Usuario) {
                    $nombre = $usuarioDetalles->nombre ?? $userSolicitante->name ?? 'Usuario';
                    
                    // Obtener foto del usuario
                    if (method_exists($usuarioDetalles, 'fotos') && 
                        $usuarioDetalles->fotos && 
                        $usuarioDetalles->fotos->isNotEmpty()) {
                        $foto = $usuarioDetalles->fotos->first();
                        $fotoUrl = asset('storage/' . $foto->ruta_foto);
                    } elseif ($usuarioDetalles->foto_perfil) {
                        $fotoUrl = asset('storage/' . $usuarioDetalles->foto_perfil);
                    }
                } else {
                    // Fallback al User model
                    $nombre = $userSolicitante->name ?? 'Usuario';
                }
                
                return [
                    'id' => $solicitud->idSolicitud,
                    'solicitud_id' => $solicitud->idSolicitud,
                    'solicitante_id' => $userSolicitante->id,
                    'nombre' => $nombre,
                    'img' => $fotoUrl,
                    'mascota_id' => $solicitud->mascota->id,
                    'mascota_nombre' => $solicitud->mascota->nombre,
                    'mascota_foto' => $solicitud->mascota->foto_principal_url ??
                                    'https://cdn.pixabay.com/photo/2017/09/25/13/12/dog-2785074_960_720.jpg',
                    'fecha_solicitud' => $solicitud->fechaSolicitud->format('d/m/Y H:i'),
                    'estado' => $solicitud->estadoSolicitud,
                    'estado_color' => $solicitud->getEstadoConColorAttribute(),
                    'unique_key' => $userSolicitante->id . '_' . $solicitud->mascota->id . '_' . $solicitud->idSolicitud
                ];
            });

            Log::info('=== FIN solicitudesRecibidas ===');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'solicitudes' => $solicitudesFormateadas,
                    'total' => $solicitudesFormateadas->count(),
                    'debug' => [
                        'mascotas_del_usuario' => $mascotasIds,
                        'ofertas_activas' => $ofertasActivasIds,
                        'usuario_id' => $usuarioId,
                        'user_id' => $user->id
                    ],
                    'estadisticas' => [
                        'pendientes' => $solicitudes->where('estadoSolicitud', 'pendiente')->count(),
                        'aprobadas' => $solicitudes->where('estadoSolicitud', 'aprobada')->count(),
                        'rechazadas' => $solicitudes->where('estadoSolicitud', 'rechazada')->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('=== ERROR en solicitudesRecibidas ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Traza: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes recibidas',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener el conteo de solicitudes pendientes para el header
     */
    public function conteoSolicitudesPendientes()
    {
        try {
            $user = Auth::user();
            
            $conteo = SolicitudAdopcion::whereHas('mascota', function($query) use ($user) {
                $query->where('usuario_id', $user->id);
            })
            ->where('estadoSolicitud', 'pendiente')
            ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'conteo' => $conteo,
                    'mensaje' => $conteo == 0 ? 'No tienes solicitudes pendientes' : 
                                ($conteo == 1 ? 'Tienes 1 solicitud de adopción' : 
                                "Tienes {$conteo} solicitudes de adopción")
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conteo de solicitudes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener TODAS las solicitudes recibidas (no solo pendientes)
     */
    public function todasSolicitudesRecibidas()
    {
        try {
            $user = Auth::user();
            
            Log::info('Obteniendo TODAS las solicitudes recibidas para el usuario: ' . $user->id);
            
            // Obtener TODAS las solicitudes para las mascotas del usuario (no solo pendientes)
            $solicitudes = SolicitudAdopcion::with([
                'mascota' => function($query) {
                    $query->with('fotos');
                },
                'usuario' => function($query) {
                    $query->with(['userable' => function($q) {
                        // Verificar si el modelo tiene relación fotos
                        $model = $q->getModel();
                        if (method_exists($model, 'fotos')) {
                            $q->with('fotos');
                        }
                    }]);
                }
            ])
            ->whereHas('mascota', function($query) use ($user) {
                $query->where('usuario_id', $user->id);
            })
            ->orderBy('fechaSolicitud', 'desc')
            ->get();

            Log::info('TODAS las solicitudes recibidas encontradas: ' . $solicitudes->count());
            
            // Debug: Mostrar IDs de las solicitudes encontradas
            $solicitudesIds = $solicitudes->pluck('idSolicitud')->toArray();
            Log::info('IDs de solicitudes encontradas: ' . implode(', ', $solicitudesIds));

            // Formatear la respuesta para el frontend
            $solicitudesFormateadas = $solicitudes->map(function($solicitud) {
                $userSolicitante = $solicitud->usuario;
                $usuarioDetalles = $userSolicitante->userable ?? null;
                
                // Obtener nombre del usuario
                $nombre = 'Usuario';
                $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
                
                if ($usuarioDetalles instanceof Usuario) {
                    $nombre = $usuarioDetalles->nombre ?? $userSolicitante->name ?? 'Usuario';
                    
                    // Obtener foto del usuario
                    if ($usuarioDetalles->fotos && $usuarioDetalles->fotos->isNotEmpty()) {
                        $foto = $usuarioDetalles->fotos->first();
                        $fotoUrl = asset('storage/' . $foto->ruta_foto);
                    } elseif ($usuarioDetalles->foto_perfil) {
                        $fotoUrl = asset('storage/' . $usuarioDetalles->foto_perfil);
                    }
                } else {
                    // Fallback al User model
                    $nombre = $userSolicitante->name ?? 'Usuario';
                }
                
                return [
                    'id' => $solicitud->idSolicitud,
                    'solicitud_id' => $solicitud->idSolicitud,
                    'solicitante_id' => $userSolicitante->id,
                    'nombre' => $nombre,
                    'img' => $fotoUrl,
                    'mascota_id' => $solicitud->mascota->id,
                    'mascota_nombre' => $solicitud->mascota->nombre,
                    'mascota_foto' => $solicitud->mascota->foto_principal_url ??
                                   'https://cdn.pixabay.com/photo/2017/09/25/13/12/dog-2785074_960_720.jpg',
                    'fecha_solicitud' => $solicitud->fechaSolicitud->format('d/m/Y H:i'),
                    'estado' => $solicitud->estadoSolicitud,
                    'estado_color' => $solicitud->getEstadoConColorAttribute(),
                    'usuario_type' => $usuarioDetalles ? get_class($usuarioDetalles) : 'No disponible'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'solicitudes' => $solicitudesFormateadas,
                    'total' => $solicitudesFormateadas->count(),
                    'user_id' => $user->id,
                    'estadisticas' => [
                        'pendientes' => $solicitudes->where('estadoSolicitud', 'pendiente')->count(),
                        'aprobadas' => $solicitudes->where('estadoSolicitud', 'aprobada')->count(),
                        'rechazadas' => $solicitudes->where('estadoSolicitud', 'rechazada')->count(),
                        'canceladas' => $solicitudes->where('estadoSolicitud', 'cancelada')->count(),
                        'todas' => $solicitudes->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener TODAS las solicitudes recibidas:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Verificar estado de ofertas para una mascota específica
     */
    public function verificarOfertasMascota($mascotaId)
    {
        try {
            $user = Auth::user();
            $usuarioId = $user->userable->id;
            
            // Verificar que la mascota pertenece al usuario
            $mascota = Mascota::where('id', $mascotaId)
                ->where('usuario_id', $usuarioId)
                ->first();
                
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada o no pertenece al usuario'
                ], 404);
            }
            
            // Obtener ofertas activas para esta mascota
            $ofertasActivas = OfertaAdopcion::where('id_mascota', $mascotaId)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->get();
                
            // Obtener solicitudes para esta mascota
            $solicitudes = SolicitudAdopcion::with(['usuarioSolicitante'])
                ->where('idMascota', $mascotaId)
                ->orderBy('fechaSolicitud', 'desc')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre
                    ],
                    'ofertas_activas' => $ofertasActivas,
                    'solicitudes' => $solicitudes->map(function($solicitud) {
                        return [
                            'id' => $solicitud->idSolicitud,
                            'estado' => $solicitud->estadoSolicitud,
                            'fecha' => $solicitud->fechaSolicitud,
                            'solicitante' => [
                                'id' => $solicitud->usuarioSolicitante->id,
                                'name' => $solicitud->usuarioSolicitante->name
                            ]
                        ];
                    }),
                    'estadisticas' => [
                        'total_solicitudes' => $solicitudes->count(),
                        'pendientes' => $solicitudes->where('estadoSolicitud', 'pendiente')->count(),
                        'con_oferta_activa' => $ofertasActivas->count() > 0
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al verificar ofertas de mascota:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de solicitudes
     */
    public function estadisticas()
    {
        try {
            $user = Auth::user();
            
            $total = SolicitudAdopcion::where('idUsuarioSolicitante', $user->id)->count();
            $pendientes = SolicitudAdopcion::where('idUsuarioSolicitante', $user->id)
                ->where('estadoSolicitud', 'pendiente')
                ->count();
            $aprobadas = SolicitudAdopcion::where('idUsuarioSolicitante', $user->id)
                ->where('estadoSolicitud', 'aprobada')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'pendientes' => $pendientes,
                    'aprobadas' => $aprobadas,
                    'rechazadas' => $total - $pendientes - $aprobadas
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

    /**
     * Obtener una solicitud específica por ID
     */

    public function show($id)
    {
        try {
            $user = Auth::user();
            
            Log::info('=== INICIO show SolicitudAdopcion ===');
            Log::info('Solicitud ID: ' . $id);
            Log::info('Usuario autenticado ID (User): ' . $user->id);
            Log::info('Usuario autenticado (userable ID): ' . ($user->userable->id ?? 'N/A'));
            
            // Cargar la solicitud con las relaciones CORRECTAS
            $solicitud = SolicitudAdopcion::with([
                'mascota' => function($query) {
                    $query->with('fotos');
                },
                'usuarioSolicitante.userable' // ← Esta es la relación correcta
            ])
            ->where('idSolicitud', $id)
            ->first();
            
            Log::info('Solicitud encontrada: ' . ($solicitud ? 'Sí' : 'No'));
            
            if (!$solicitud) {
                Log::error('Solicitud no encontrada con ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada'
                ], 404);
            }
            
            // Obtener el ID del Usuario del user autenticado
            $usuarioAutenticadoId = $user->userable->id ?? null;
            
            if (!$usuarioAutenticadoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene perfil completo'
                ], 403);
            }
            
            // Verificar que la mascota pertenece al usuario autenticado
            // COMPARANDO con usuario_id (que es el ID de la tabla Usuario)
            if ($solicitud->mascota->usuario_id !== $usuarioAutenticadoId) {
                Log::error('Usuario no autorizado para ver esta solicitud', [
                    'mascota_usuario_id' => $solicitud->mascota->usuario_id,
                    'usuario_autenticado_id' => $usuarioAutenticadoId,
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver esta solicitud'
                ], 403);
            }
            
            // Obtener datos del solicitante - CORREGIDO
            $userSolicitante = $solicitud->usuarioSolicitante; // ← Esto debe devolver el User
            $usuarioDetalles = $userSolicitante->userable ?? null;
            
            Log::info('Información del solicitante:');
            Log::info('- User ID (tabla User): ' . $userSolicitante->id);
            Log::info('- Userable type: ' . ($usuarioDetalles ? get_class($usuarioDetalles) : 'No disponible'));
            Log::info('- Userable ID: ' . ($usuarioDetalles->id ?? 'N/A'));
            
            $solicitanteInfo = null;
            if ($usuarioDetalles instanceof \App\Models\Usuario) {
                // Obtener foto principal
                $fotoPrincipal = $usuarioDetalles->fotos()
                    ->where('es_principal', true)
                    ->first();
                
                $fotoUrl = $fotoPrincipal 
                    ? asset('storage/' . $fotoPrincipal->ruta_foto)
                    : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
                
                $solicitanteInfo = [
                    'id' => $usuarioDetalles->id, // ← ID del Usuario, no del User
                    'user_id' => $userSolicitante->id, // ← ID del User
                    'nombre' => $usuarioDetalles->nombre ?? 'Usuario',
                    'edad' => $usuarioDetalles->edad ?? null,
                    'descripcion' => $usuarioDetalles->descripcion ?? null,
                    'foto_perfil_url' => $fotoUrl,
                    'experiencia' => $usuarioDetalles->caracteristicas->experiencia ?? null
                ];
                
                Log::info('- Nombre del solicitante: ' . $solicitanteInfo['nombre']);
                Log::info('- ID del solicitante (Usuario): ' . $solicitanteInfo['id']);
                Log::info('- ID del solicitante (User): ' . $solicitanteInfo['user_id']);
            } else {
                // Fallback si no es instancia de Usuario
                $solicitanteInfo = [
                    'id' => $userSolicitante->id,
                    'nombre' => $userSolicitante->name ?? 'Usuario',
                    'foto_perfil_url' => null,
                    'descripcion' => null
                ];
                
                Log::info('- Nombre (fallback): ' . $solicitanteInfo['nombre']);
            }
            
            // Obtener la oferta relacionada
            Log::info('Buscando oferta para mascota ID: ' . $solicitud->idMascota);
            $oferta = OfertaAdopcion::where('id_mascota', $solicitud->idMascota)
                ->where('estado_oferta', 'publicada')
                ->first();
            
            Log::info('Oferta encontrada: ' . ($oferta ? 'Sí' : 'No'));
            
            // Preparar respuesta
            $responseData = [
                'success' => true,
                'data' => [
                    'solicitud' => [
                        'idSolicitud' => $solicitud->idSolicitud,
                        'idUsuarioSolicitante' => $solicitud->idUsuarioSolicitante, // ← Este es el ID del User
                        'usuarioSolicitanteId' => $usuarioDetalles->id ?? null, // ← Este es el ID del Usuario
                        'idMascota' => $solicitud->idMascota,
                        'estadoSolicitud' => $solicitud->estadoSolicitud,
                        'aceptóTerminos' => $solicitud->aceptóTerminos,
                        'fechaSolicitud' => $solicitud->fechaSolicitud,
                    ],
                    'solicitante' => $solicitanteInfo, // ← Información completa del solicitante
                    'oferta' => $oferta ? [
                        'id_oferta' => $oferta->id_oferta,
                        'estado_oferta' => $oferta->estado_oferta,
                        'permiso_historial_medico' => $oferta->permiso_historial_medico,
                        'permiso_contacto_tutor' => $oferta->permiso_contacto_tutor,
                        'created_at' => $oferta->created_at,
                    ] : null
                ]
            ];
            
            Log::info('Respuesta preparada exitosamente');
            Log::info('ID del solicitante en respuesta: ' . ($solicitanteInfo['id'] ?? 'N/A'));
            Log::info('Nombre del solicitante en respuesta: ' . ($solicitanteInfo['nombre'] ?? 'N/A'));
            
            return response()->json($responseData);
            
        } catch (\Exception $e) {
            Log::error('=== ERROR en SolicitudAdopcionController@show ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la solicitud',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Aprobar una solicitud de adopción
     */
     public function aprobar($id)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();

            Log::info('=== APROBAR SOLICITUD ===');
            Log::info('User ID: ' . $user->id);
            Log::info('User type: ' . get_class($user));

            $usuarioAutenticadoId = $user->userable->id ?? null;

            Log::info('Usuario autenticado ID: ' . $usuarioAutenticadoId);
            Log::info('Userable type: ' . ($user->userable ? get_class($user->userable) : 'N/A'));
        
            if (!$usuarioAutenticadoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene perfil completo'
                ], 403);
            }
            
            $solicitud = SolicitudAdopcion::with('mascota')
                ->where('idSolicitud', $id)
                ->first();
            
            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada'
                ], 404);
            }
            
            // ✅ GUARDAR EL TUTOR ORIGINAL ANTES DE TRANSFERIR
            $tutorOriginalId = $solicitud->mascota->usuario_id;
            
            // Verificar que la mascota pertenece al usuario
            if ($tutorOriginalId !== $usuarioAutenticadoId) {
                Log::error('Usuario no autorizado para aprobar esta solicitud', [
                    'mascota_usuario_id' => $tutorOriginalId,
                    'usuario_autenticado_id' => $usuarioAutenticadoId,
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para aprobar esta solicitud'
                ], 403);
            }
            
            // Verificar que esté en estado pendiente
            if ($solicitud->estadoSolicitud !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud no está en estado pendiente'
                ], 400);
            }
            
            // ✅ ✅ ✅ NUEVA VALIDACIÓN: VERIFICAR INTERACCIONES EN EL CHAT
            $validacionChat = $this->validarChatParaAdopcion($solicitud, $user);
            if (!$validacionChat['valido']) {
                return response()->json([
                    'success' => false,
                    'message' => $validacionChat['mensaje'],
                    'data' => $validacionChat['detalles'] ?? [],
                    'codigo_error' => 'INTERACCIONES_INSUFICIENTES'
                ], 400);
            }
            
            Log::info('✅ Validación de chat superada para solicitud: ' . $id, [
                'interacciones' => $validacionChat['detalles']['interacciones'] ?? 0,
                'requeridas' => 5,
                'chat_id' => $validacionChat['detalles']['chat_id'] ?? null
            ]);
            
            // ✅ 1. TRANSFERIR MASCOTA
            $transferenciaExitosa = $this->transferirMascota($solicitud);
            
            if (!$transferenciaExitosa) {
                throw new \Exception('Error al transferir la mascota');
            }
            
            // ✅ 2. Actualizar estado de solicitud
            $solicitud->estadoSolicitud = 'aprobada';
            $solicitud->save();
            
            // ✅ 3. Cambiar estado de la oferta a cerrada
            OfertaAdopcion::where('id_mascota', $solicitud->idMascota)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->update(['estado_oferta' => 'cerrada']);
                
            // ✅ 4. Crear proceso de adopción (PASANDO el tutor original)
            $procesoController = new ProcesoAdopcionController();
            $procesoResponse = $procesoController->crearDesdeSolicitudAprobada(
                $solicitud->idSolicitud, 
                $tutorOriginalId  // ← Pasar el tutor original
            );
            
            if (!$procesoResponse->getData()->success) {
                Log::error('Error al crear proceso de adopción automático', [
                    'solicitud_id' => $solicitud->idSolicitud,
                    'error' => $procesoResponse->getData()->message ?? 'Desconocido'
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada y mascota transferida exitosamente',
                'data' => [
                    'solicitud' => $solicitud,
                    'transferencia_realizada' => true,
                    'tutor_original_id' => $tutorOriginalId,
                    'nuevo_tutor_id' => $solicitud->mascota->usuario_id,
                    'validacion_chat' => $validacionChat['detalles'] ?? []
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al aprobar solicitud:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud: ' . $e->getMessage(),
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Método para validar si el chat asociado a la solicitud está listo para adopción
     */
    private function validarChatParaAdopcion(SolicitudAdopcion $solicitud, $user)
    {
        try {
            // Buscar chat asociado a la solicitud
            $chat = Chat::where('solicitud_id', $solicitud->idSolicitud)
                ->where(function($query) use ($user) {
                    $query->where('user1_id', $user->id)
                          ->orWhere('user2_id', $user->id);
                })
                ->first();
            
            if (!$chat) {
                return [
                    'valido' => false,
                    'mensaje' => 'No se puede aprobar la solicitud: No se ha iniciado un chat con el solicitante',
                    'detalles' => [
                        'error_type' => 'CHAT_NO_INICIADO',
                        'requiere_chat' => true,
                        'sugerencia' => 'Primero debes iniciar una conversación con el solicitante desde la solicitud'
                    ]
                ];
            }
            
            // Verificar si el chat tiene suficientes interacciones
            $interaccionesUsuario = $chat->interaccionesDeUsuario($user->id);
            $interaccionesOtroUsuario = $chat->interaccionesOtroUsuario($user->id);
            
            // Calcular el mínimo de interacciones (ping-pong)
            $minimoInteracciones = min($interaccionesUsuario, $interaccionesOtroUsuario);
            
            if ($minimoInteracciones < 5) {
                $faltan = 5 - $minimoInteracciones;
                
                return [
                    'valido' => false,
                    'mensaje' => "Se requieren más interacciones en el chat para proceder con la adopción",
                    'detalles' => [
                        'error_type' => 'INTERACCIONES_INSUFICIENTES',
                        'interacciones_actuales' => $minimoInteracciones,
                        'interacciones_requeridas' => 5,
                        'faltan_mensajes' => $faltan,
                        'mensajes_usuario_actual' => $interaccionesUsuario,
                        'mensajes_solicitante' => $interaccionesOtroUsuario,
                        'chat_id' => $chat->chat_id,
                        'esta_listo' => false,
                        'progreso' => ($minimoInteracciones / 5) * 100,
                        'recomendacion' => "Faltan {$faltan} mensajes intercambiados para habilitar la aprobación"
                    ]
                ];
            }
            
            // Si llega aquí, el chat está listo
            return [
                'valido' => true,
                'mensaje' => 'Chat validado exitosamente',
                'detalles' => [
                    'chat_id' => $chat->chat_id,
                    'interacciones' => $minimoInteracciones,
                    'mensajes_usuario_actual' => $interaccionesUsuario,
                    'mensajes_solicitante' => $interaccionesOtroUsuario,
                    'esta_listo' => true,
                    'progreso' => 100,
                    'fecha_habilitacion' => $chat->fecha_habilitado_adopcion,
                    'validacion_exitosa' => true
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al validar chat para adopción:', [
                'solicitud_id' => $solicitud->idSolicitud,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'mensaje' => 'Error al validar el chat: ' . $e->getMessage(),
                'detalles' => [
                    'error_type' => 'ERROR_VALIDACION',
                    'error_message' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Método para transferir mascota al adoptante
     */
   private function transferirMascota(SolicitudAdopcion $solicitud)
    {
        try {
            $user = Auth::user();
            $usuarioAutenticadoId = $user->userable->id ?? null;
            
            $mascota = Mascota::find($solicitud->idMascota);
            
            if (!$mascota) {
                throw new \Exception('Mascota no encontrada');
            }
            
            // Verificar que el tutor actual es quien dice ser
            if ($mascota->usuario_id !== $usuarioAutenticadoId) {
                throw new \Exception('El usuario no es el tutor actual de la mascota');
            }
            
            // ✅ CORREGIDO: Primero obtener el User del solicitante, luego su Usuario
            $userSolicitante = User::find($solicitud->idUsuarioSolicitante);
            if (!$userSolicitante) {
                throw new \Exception('Usuario solicitante (User) no encontrado');
            }
            
            // Obtener el Usuario del solicitante (userable)
            $usuarioSolicitante = $userSolicitante->userable;
            if (!$usuarioSolicitante || !$usuarioSolicitante instanceof Usuario) {
                throw new \Exception('Perfil de usuario (Usuario) no encontrado para el solicitante');
            }
            
            $usuarioSolicitanteId = $usuarioSolicitante->id;
            
            Log::info('Transferiendo mascota - IDs:', [
                'mascota_id' => $mascota->id,
                'tutor_actual_id' => $usuarioAutenticadoId,
                'tutor_nuevo_usuario_id' => $usuarioSolicitanteId,
                'user_solicitante_id' => $userSolicitante->id,
                'usuario_solicitante_id' => $usuarioSolicitanteId
            ]);
            
            // ✅ PASO 1: REGISTRAR TRANSFERENCIA EN EL HISTORIAL
            $transferencia = HistorialTransferenciaMascota::create([
                'mascota_id' => $mascota->id,
                'tutor_anterior_id' => $usuarioAutenticadoId, // ID de Usuario, no User
                'tutor_nuevo_id' => $usuarioSolicitanteId,    // ID de Usuario, no User
                'solicitud_adopcion_id' => $solicitud->idSolicitud,
                'proceso_adopcion_id' => null,
                'fecha_transferencia' => now(),
                'motivo' => 'adopcion',
                'observaciones' => 'Transferencia por aprobación de solicitud de adopción',
                'datos_adicionales' => [
                    'aprobado_por_user_id' => $user->id,
                    'aprobado_por_usuario_id' => $usuarioAutenticadoId,
                    'aprobado_en' => now()->toDateTimeString(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            ]);
            
            // ✅ PASO 2: ACTUALIZAR TUTOR ACTUAL DE LA MASCOTA
            $mascota->usuario_id = $usuarioSolicitanteId; // Asignar ID de Usuario
            $mascota->save();
            
            Log::info('Transferencia de mascota registrada exitosamente', [
                'transferencia_id' => $transferencia->id_transferencia,
                'mascota_id' => $mascota->id,
                'nombre_mascota' => $mascota->nombre,
                'tutor_anterior_usuario_id' => $usuarioAutenticadoId,
                'tutor_nuevo_usuario_id' => $usuarioSolicitanteId,
                'solicitud_id' => $solicitud->idSolicitud
            ]);
            
            return $transferencia;
            
        } catch (\Exception $e) {
            Log::error('Error al transferir mascota', [
                'error' => $e->getMessage(),
                'solicitud_id' => $solicitud->idSolicitud,
                'solicitud_data' => [
                    'idUsuarioSolicitante' => $solicitud->idUsuarioSolicitante,
                    'idMascota' => $solicitud->idMascota
                ],
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Guardar notas en una solicitud
     */
    public function guardarNotas(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notas' => 'nullable|string|max:2000'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $solicitud = SolicitudAdopcion::with('mascota')
                ->where('idSolicitud', $id)
                ->first();
            
            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada'
                ], 404);
            }
            
            // Verificar que la mascota pertenece al usuario
            if ($solicitud->mascota->usuario_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para modificar esta solicitud'
                ], 403);
            }
            
            // Guardar notas (necesitarás agregar un campo 'notas' a tu modelo)
            $solicitud->notas = $request->notas;
            $solicitud->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Notas guardadas exitosamente',
                'data' => $solicitud
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar notas:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar notas',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}