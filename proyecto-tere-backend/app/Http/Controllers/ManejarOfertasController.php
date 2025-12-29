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
    // No necesitamos constructor si usamos métodos estáticos

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

            // Preparar la respuesta con la información completa
            $mascota = $oferta->mascota;
            
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
            
            // Consulta base
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion'
            ])
            ->where('estado_oferta', 'publicada');
            
            Log::info('Consulta base creada. Total ofertas publicadas:', [
                'count' => $query->count()
            ]);
            
            // Aplicar filtros usando el controlador de filtros
            $filtrosController = new FiltrosMascotasController();
            $query = $filtrosController->aplicarFiltros($query, $request);
            
            // Obtener SQL final para debugging
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            Log::info('Consulta SQL final:', [
                'sql' => $sql,
                'bindings' => $bindings
            ]);
            
            // Obtener ofertas
            $ofertas = $query->orderBy('created_at', 'desc')->get();
            
            Log::info('Ofertas encontradas después de filtros:', [
                'count' => $ofertas->count()
            ]);
            
            // Mapear resultados
            $ofertasFormateadas = $ofertas->map(function($oferta) {
                $mascota = $oferta->mascota;
                
                // Determinar rango etario para mostrar
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
                    'mascota' => [
                        'id' => $mascota->id,
                        'nombre' => $mascota->nombre,
                        'especie' => $mascota->especie,
                        'sexo' => $mascota->sexo,
                        'castrado' => $mascota->castrado,
                        'rango_etario' => $rangoEtario,
                        'foto_principal_url' => $mascota->foto_principal_url,
                        'caracteristicas' => $mascota->caracteristicas
                    ]
                ];
            });

            Log::info('Respuesta final preparada, ofertas encontradas:', [
                'count' => $ofertasFormateadas->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $ofertasFormateadas,
                'count' => $ofertasFormateadas->count(),
                'debug' => [
                    'request_params' => $request->all(),
                    'total_filtered' => $ofertasFormateadas->count()
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
            
            // Registrar la interacción
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
            
            // Obtener IDs de mascotas que el usuario ya ha visto/interactuado
            $mascotasInteractuadas = InteraccionSwipeUsuario::where('usuario_id', $usuario->id)
                ->pluck('mascota_id')
                ->toArray();
            
            // Consulta base
            $query = OfertaAdopcion::with([
                'mascota.caracteristicas',
                'mascota.fotos',
                'mascota.edadRelacion',
                'mascota.usuario'
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
                    // Crear una request temporal para usar el controlador de filtros
                    $tempRequest = new Request($filtros);
                    $filtrosController = new FiltrosMascotasController();
                    $query = $filtrosController->aplicarFiltros($query, $tempRequest);
                }
            }
            
            // Ordenar y limitar
            $ofertas = $query->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($oferta) {
                    $mascota = $oferta->mascota;
                    
                    // Verificar que la mascota tenga fotos
                    $fotos = $mascota->fotos ?? collect([]);
                    
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
}