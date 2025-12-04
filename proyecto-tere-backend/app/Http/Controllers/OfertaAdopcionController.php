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
            
            $ofertas = OfertaAdopcion::with(['mascota' => function($query) {
                // CORRECCIÓN: No seleccionar 'foto' porque no existe como campo
                $query->select('id', 'nombre', 'especie');
            }])
            ->where('id_usuario_responsable', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($oferta) {
                // Agregar manualmente la URL de la foto
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
            
            // Log para depuración
            Log::info('Creando oferta de adopción', [
                'usuario_id' => $user->id,
                'datos_recibidos' => $request->all()
            ]);
            
            // Validar datos
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
            
            // Verificar que la mascota pertenece al usuario
            $mascota = Mascota::with(['fotos' => function($query) {
                $query->where('es_principal', true)->first();
            }])
            ->where('id', $request->mascotaId)
            ->where('usuario_id', $user->id)
            ->first();
            
            if (!$mascota) {
                Log::error('Mascota no pertenece al usuario', [
                    'mascota_id' => $request->mascotaId,
                    'usuario_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'La mascota no existe o no pertenece al usuario'
                ], 404);
            }
            
            // Verificar que no existe una oferta activa para esta mascota
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
            
            // Crear la oferta
            $oferta = OfertaAdopcion::create([
                'id_mascota' => $request->mascotaId,
                'id_usuario_responsable' => $user->id,
                'estado_oferta' => 'publicada',
                'permiso_historial_medico' => $request->permisos['compartirHistorialMedico'],
                'permiso_contacto_tutor' => $request->permisos['compartirMediosContacto'],
            ]);
            
            Log::info('Oferta creada exitosamente', ['oferta_id' => $oferta->id_oferta]);
            
            DB::commit();
            
            // Preparar respuesta sin cargar relaciones problemáticas
            $responseData = [
                'success' => true,
                'message' => 'Oferta de adopción creada exitosamente',
                'data' => [
                    'id_oferta' => $oferta->id_oferta,
                    'id_mascota' => $oferta->id_mascota,
                    'estado_oferta' => $oferta->estado_oferta,
                    'mascota_nombre' => $mascota->nombre,
                    'mascota_foto_url' => $mascota->foto_principal_url, // Usar el accessor
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
    // En OfertaAdopcionController.php
    public function show($id)
    {
        try {
            // No verificar que sea del usuario autenticado
            // Solo verificar que exista y esté publicada
            $oferta = OfertaAdopcion::with([
                'mascota.fotos',
                'mascota.caracteristicas',
                'mascota.edadRelacion',
                'usuarioResponsable' // Agregar esta relación
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
            
            // Preparar datos de la mascota
            $mascota = $oferta->mascota;
            
            // Decodificar características si es necesario
            $caracteristicas = is_string($mascota->caracteristicas ?? '') 
                ? json_decode($mascota->caracteristicas, true) 
                : ($mascota->caracteristicas ?? []);
            
            $datosMascota = [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'sexo' => $mascota->sexo,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'usuario_id' => $mascota->usuario_id,
                'caracteristicas' => $caracteristicas,
                'fotos' => $mascota->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url ?? asset('storage/' . $foto->ruta_foto),
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
                'foto_principal_url' => $mascota->foto_principal_url,
            ];
            
            // Información del usuario responsable
            $usuarioResponsable = [
                'id' => $oferta->usuarioResponsable->id ?? null,
                'nombre' => $oferta->usuarioResponsable->nombre ?? 'Usuario',
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
                    'mascota' => $datosMascota,
                    'usuario_responsable' => $usuarioResponsable
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en OfertaAdopcionController@show: ' . $e->getMessage());
            
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
            
            // Buscar la oferta activa
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
            
            // Cambiar estado a cancelada
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
            
            // Buscar la oferta activa para esta mascota
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
            
            // Cambiar estado a cancelada
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
            
            Log::info('Obteniendo mascotas disponibles para usuario', [
                'usuario_id' => $user->id,
                'user_class' => get_class($user)
            ]);
            
            // Obtener IDs de mascotas que ya tienen ofertas activas
            $mascotasConOfertaActiva = OfertaAdopcion::where('id_usuario_responsable', $user->id)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->pluck('id_mascota')
                ->toArray();
            
            Log::info('Mascotas con oferta activa', ['ids' => $mascotasConOfertaActiva]);
            
            // Obtener mascotas del usuario que no están en ofertas activas
            // IMPORTANTE: Verificar el nombre exacto de la columna de usuario en la tabla mascotas
            // Si no es 'usuario_id', podría ser 'user_id' o 'id_usuario'
            $mascotas = Mascota::where('usuario_id', $user->id) // <-- CORREGIR ESTO SI ES NECESARIO
                ->whereNotIn('id', $mascotasConOfertaActiva)
                ->whereNull('deleted_at') // Solo mascotas no eliminadas
                ->get()
                ->map(function($mascota) {
                    $caracteristicas = is_string($mascota->caracteristicas) 
                        ? json_decode($mascota->caracteristicas, true) 
                        : $mascota->caracteristicas;
                    
                    return [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'foto_url' => $mascota->foto_principal_url, // Usar el accessor
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
            
            $ofertas = OfertaAdopcion::with(['mascota' => function($query) {
                // No seleccionar 'foto' porque no existe
                $query->select('id', 'nombre', 'especie');
            }])
            ->where('id_usuario_responsable', $user->id)
            ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
            ->get()
            ->map(function($oferta) {
                return [
                    'id' => $oferta->mascota->id,
                    'nombre' => $oferta->mascota->nombre,
                    'foto' => $oferta->mascota->foto_principal_url, // Usar el accessor
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
     */
    public function getOfertasDisponibles(Request $request)
    {
        Log::info('=== INICIO getOfertasDisponibles ===');
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
            
            Log::info('Usuario autenticado:', ['id' => $user->id, 'email' => $user->email]);
            
            // ✅ CORRECCIÓN: Usar solo campos que existen en la tabla usuarios
            $query = OfertaAdopcion::with([
                'mascota' => function($query) {
                    $query->with(['fotos' => function($q) {
                        $q->where('es_principal', true);
                    }]);
                },
                // Solo campos que existen en la tabla usuarios
                'usuarioResponsable:id,nombre' // ← Solo 'nombre', no 'email' ni 'apellido'
            ])
            ->where('estado_oferta', 'publicada');
            
            Log::info('Consulta base creada, excluyendo ofertas del usuario: ' . $user->id);
            
            // Excluir ofertas del usuario autenticado
            $query->where('id_usuario_responsable', '!=', $user->id);
            
            Log::info('Ejecutando consulta...');
            $ofertas = $query->orderBy('created_at', 'desc')->get();
            Log::info('Total ofertas encontradas: ' . $ofertas->count());
            
            $ofertasFormateadas = $ofertas->map(function($oferta) {
                try {
                    // Verificar relaciones
                    if (!$oferta->mascota) {
                        Log::warning('Oferta sin mascota: ' . $oferta->id_oferta);
                        return null;
                    }
                    
                    if (!$oferta->usuarioResponsable) {
                        Log::warning('Oferta sin usuario responsable: ' . $oferta->id_oferta);
                        return null;
                    }
                    
                    // Calcular rango etario
                    $edadMeses = $oferta->mascota->edad_meses ?? 0;
                    $rangoEtario = 'Adulto';
                    
                    if ($edadMeses <= 12) {
                        $rangoEtario = 'Cachorro';
                    } elseif ($edadMeses <= 36) {
                        $rangoEtario = 'Joven';
                    } elseif ($edadMeses <= 84) {
                        $rangoEtario = 'Adulto';
                    } else {
                        $rangoEtario = 'Abuelo';
                    }
                    
                    return [
                        'id_oferta' => $oferta->id_oferta,
                        'mascota' => [
                            'id' => $oferta->mascota->id,
                            'nombre' => $oferta->mascota->nombre ?? 'Sin nombre',
                            'especie' => $oferta->mascota->especie ?? 'Desconocido',
                            'sexo' => $oferta->mascota->sexo ?? 'Desconocido',
                            'edad_meses' => $edadMeses,
                            'edad_formateada' => $oferta->mascota->edad_formateada ?? 'Edad no especificada',
                            'rango_etario' => $rangoEtario,
                            'caracteristicas' => is_string($oferta->mascota->caracteristicas ?? '') 
                                ? json_decode($oferta->mascota->caracteristicas, true) 
                                : ($oferta->mascota->caracteristicas ?? []),
                            'foto_principal_url' => $oferta->mascota->foto_principal_url ?? null,
                        ],
                        'usuario_responsable' => [
                            'id' => $oferta->usuarioResponsable->id,
                            'nombre' => $oferta->usuarioResponsable->nombre ?? 'Usuario',
                            // No incluir 'email' ni 'ubicacion' porque no existen en la tabla
                        ],
                        'permisos' => [
                            'historial_medico' => $oferta->permiso_historial_medico ?? false,
                            'contacto_tutor' => $oferta->permiso_contacto_tutor ?? false,
                        ],
                        'estado_oferta' => $oferta->estado_oferta,
                        'created_at' => $oferta->created_at ? $oferta->created_at->format('d/m/Y H:i') : 'Fecha no disponible',
                    ];
                } catch (\Exception $e) {
                    Log::error('Error procesando oferta ' . ($oferta->id_oferta ?? 'unknown') . ': ' . $e->getMessage());
                    return null;
                }
            })->filter()->values();
            
            Log::info('Ofertas formateadas: ' . $ofertasFormateadas->count());
            
            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'total' => $ofertasFormateadas->count(),
                'debug' => [
                    'user_id' => $user->id,
                    'filters_applied' => $request->all(),
                    'query_time' => now()->toDateTimeString(),
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
     * Obtener oferta por ID de mascota
     */
    public function getOfertaPorMascota($mascotaId)
    {
        try {
            $user = Auth::user();
            
            // Verificar que la mascota pertenece al usuario
            $mascota = Mascota::where('id', $mascotaId)
                ->where('usuario_id', $user->id)
                ->first();
            
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada o no autorizado'
                ], 404);
            }
            
            // Buscar oferta activa para esta mascota
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
            
            // Preparar datos de la mascota
            $mascotaData = [
                'id' => $oferta->mascota->id,
                'nombre' => $oferta->mascota->nombre,
                'especie' => $oferta->mascota->especie,
                'raza' => $oferta->mascota->raza,
                'sexo' => $oferta->mascota->sexo,
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