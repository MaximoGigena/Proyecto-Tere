<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAdopcion;
use App\Models\OfertaAdopcion;
use App\Models\Mascota;
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
            
            // Obtener las solicitudes para las mascotas del usuario
            $solicitudes = SolicitudAdopcion::with([
                'mascota' => function($query) {
                    $query->with('fotos');
                },
                'usuario' => function($query) {
                    // CORREGIDO: Cargar userable sin intentar cargar fotos si no existen
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
            ->where('estadoSolicitud', 'pendiente')
            ->orderBy('fechaSolicitud', 'desc')
            ->get();

            Log::info('Solicitudes encontradas: ' . $solicitudes->count());

            Log::info('DEBUG - Solicitudes encontradas:');
                foreach ($solicitudes as $solicitud) {
                    Log::info('Solicitud ID: ' . $solicitud->idSolicitud . 
                            ' | Mascota ID: ' . $solicitud->idMascota . 
                            ' | Mascota Nombre: ' . $solicitud->mascota->nombre . 
                            ' | Usuario Solicitante: ' . $solicitud->idUsuarioSolicitante);
                }

            // Formatear la respuesta para el frontend
            $solicitudesFormateadas = $solicitudes->map(function($solicitud) {
                $userSolicitante = $solicitud->usuario;
                $usuarioDetalles = $userSolicitante->userable ?? null;
                
                // Obtener nombre del usuario
                $nombre = 'Usuario';
                $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
                
                if ($usuarioDetalles instanceof \App\Models\Usuario) {
                    $nombre = $usuarioDetalles->nombre ?? $userSolicitante->name ?? 'Usuario';
                    
                    // Obtener foto del usuario (verificar si existe método)
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
                
                // En el método solicitudesRecibidas(), dentro del map(), cambia:
                return [
                    'id' => $solicitud->idSolicitud,
                    'solicitud_id' => $solicitud->idSolicitud,
                    'solicitante_id' => $userSolicitante->id, // ¡ESTA ES LA CLAVE!
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
            Log::info('Usuario autenticado ID: ' . $user->id);
            
            // Cargar la solicitud con todas las relaciones (CORREGIDO)
            $solicitud = SolicitudAdopcion::with([
                'mascota' => function($query) {
                    $query->with('fotos');
                },
                'usuario' => function($query) {
                    // CORREGIDO: Solo cargar userable, no fotos directamente
                    $query->with(['userable' => function($q) {
                        // Si userable es Usuario, y Usuario tiene relación fotos
                        if (method_exists($q->getModel(), 'fotos')) {
                            $q->with('fotos');
                        }
                    }]);
                }
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
            
            // Verificar que la mascota pertenece al usuario autenticado
            Log::info('Verificando propiedad de mascota...');
            Log::info('Usuario dueño de mascota: ' . ($solicitud->mascota->usuario_id ?? 'No disponible'));
            Log::info('Usuario autenticado: ' . $user->id);
            
            if ($solicitud->mascota->usuario_id !== $user->id) {
                Log::error('Usuario no autorizado para ver esta solicitud');
                Log::error('Mascota pertenece a usuario: ' . $solicitud->mascota->usuario_id);
                Log::error('Usuario autenticado: ' . $user->id);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver esta solicitud'
                ], 403);
            }
            
            // Obtener datos del solicitante
            $userSolicitante = $solicitud->usuario;
            $usuarioDetalles = $userSolicitante->userable ?? null;
            
            Log::info('Información del solicitante:');
            Log::info('- User ID: ' . $userSolicitante->id);
            Log::info('- Userable type: ' . get_class($usuarioDetalles ?? 'No disponible'));
            
            $solicitanteInfo = null;
            if ($usuarioDetalles instanceof \App\Models\Usuario) {
                $solicitanteInfo = [
                    'id' => $userSolicitante->id,
                    'nombre' => $usuarioDetalles->nombre ?? $userSolicitante->name ?? 'Usuario',
                    'foto_perfil_url' => $usuarioDetalles->foto_perfil_url ?? null,
                    'descripcion' => $usuarioDetalles->descripcion ?? null
                ];
                
                Log::info('- Nombre: ' . $solicitanteInfo['nombre']);
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
                        'idUsuarioSolicitante' => $solicitud->idUsuarioSolicitante,
                        'idMascota' => $solicitud->idMascota,
                        'estadoSolicitud' => $solicitud->estadoSolicitud,
                        'aceptóTerminos' => $solicitud->aceptóTerminos,
                        'fechaSolicitud' => $solicitud->fechaSolicitud,
                    ],
                    'solicitante' => $solicitanteInfo,
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
            
            return response()->json($responseData);
            
        } catch (\Exception $e) {
            Log::error('=== ERROR en SolicitudAdopcionController@show ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Traza completa:');
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la solicitud',
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
     * Aprobar una solicitud de adopción
     */
    public function aprobar($id)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
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
            if ($tutorOriginalId !== $user->id) {
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
                    'nuevo_tutor_id' => $solicitud->mascota->usuario_id
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
     * Método para transferir mascota al adoptante
     */
   private function transferirMascota(SolicitudAdopcion $solicitud)
    {
        try {
            $user = Auth::user();
            $mascota = Mascota::find($solicitud->idMascota);
            
            if (!$mascota) {
                throw new \Exception('Mascota no encontrada');
            }
            
            // Verificar que el tutor actual es quien dice ser
            if ($mascota->usuario_id !== $user->id) {
                throw new \Exception('El usuario no es el tutor actual de la mascota');
            }
            
            // Verificar que el adoptante existe
            $adoptante = Usuario::find($solicitud->idUsuarioSolicitante);
            if (!$adoptante) {
                throw new \Exception('Usuario adoptante no encontrado');
            }
            
            // ✅ PASO 1: REGISTRAR TRANSFERENCIA EN EL HISTORIAL
            $transferencia = HistorialTransferenciaMascota::create([
                'mascota_id' => $mascota->id,
                'tutor_anterior_id' => $user->id,
                'tutor_nuevo_id' => $solicitud->idUsuarioSolicitante,
                'solicitud_adopcion_id' => $solicitud->idSolicitud,
                'proceso_adopcion_id' => null, // Se llenará después cuando se cree el proceso
                'fecha_transferencia' => now(),
                'motivo' => 'adopcion',
                'observaciones' => 'Transferencia por aprobación de solicitud de adopción',
                'datos_adicionales' => [
                    'aprobado_por' => $user->id,
                    'aprobado_en' => now()->toDateTimeString(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            ]);
            
            // ✅ PASO 2: ACTUALIZAR TUTOR ACTUAL DE LA MASCOTA
            $mascota->usuario_id = $solicitud->idUsuarioSolicitante;
            $mascota->save();
            
            Log::info('Transferencia de mascota registrada', [
                'transferencia_id' => $transferencia->id_transferencia,
                'mascota_id' => $mascota->id,
                'nombre_mascota' => $mascota->nombre,
                'tutor_anterior' => $user->id,
                'tutor_nuevo' => $solicitud->idUsuarioSolicitante,
                'solicitud_id' => $solicitud->idSolicitud
            ]);
            
            return $transferencia;
            
        } catch (\Exception $e) {
            Log::error('Error al transferir mascota', [
                'error' => $e->getMessage(),
                'solicitud_id' => $solicitud->idSolicitud,
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