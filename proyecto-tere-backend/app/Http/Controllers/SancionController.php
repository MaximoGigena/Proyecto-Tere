<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Denuncia;
use App\Models\Sancion;
use App\Models\HistorialSancion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Notificacion;

class SancionController extends Controller
{
    /**
     * Aplicar sanción basada en denuncia
     */
    public function aplicarSancion(Request $request, $denunciaId)
    {
        try {
            /** @var User $usuario */
            $usuario = Auth::user();
            
            if (!$usuario->isAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para aplicar sanciones'
                ], 403);
            }

            // Validar datos
            $validator = Validator::make($request->all(), [
                'tipo' => 'required|in:' . implode(',', Sancion::TIPOS),
                'nivel' => 'required|in:' . implode(',', array_keys(Sancion::NIVELES)),
                'razon' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'duracion_dias' => 'nullable|integer|min:1|max:365',
                'restricciones' => 'nullable|array',
                'restricciones.*' => 'in:' . implode(',', Sancion::RESTRICCIONES),
                'notas_admin' => 'nullable|string|max:2000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener denuncia
            $denuncia = Denuncia::with('usuarioDenunciado')->findOrFail($denunciaId);

            // Verificar si la denuncia está resuelta
            if ($denuncia->estado !== 'resuelta') {
                $denuncia->update([
                    'estado' => 'resuelta',
                    'resuelta_en' => now(),
                    'notas_admin' => ($denuncia->notas_admin ? $denuncia->notas_admin . "\n\n" : '') . 
                                'Denuncia resuelta automáticamente al aplicar sanción.'
                ]);
            }

            // Verificar si ya existe sanción activa para esta denuncia
            $sancionExistente = Sancion::where('denuncia_id', $denunciaId)
                ->where('estado', 'ACTIVA')
                ->first();

            if ($sancionExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una sanción activa para esta denuncia'
                ], 400);
            }

            // Calcular fechas
            $fechaInicio = now();
            $fechaFin = null;
            $duracionDias = $request->duracion_dias;

            if ($duracionDias) {
                $fechaFin = $fechaInicio->copy()->addDays($duracionDias);
            }

            // Determinar restricciones automáticas según tipo y nivel
            $restricciones = $request->restricciones ?? $this->determinarRestriccionesAutomaticas(
                $request->tipo, 
                $request->nivel
            );

            // Crear sanción
            $sancion = Sancion::create([
                'usuario_id' => $denuncia->usuario_denunciado_id,
                'denuncia_id' => $denunciaId,
                'tipo' => $request->tipo,
                'nivel' => $request->nivel,
                'razon' => $request->razon,
                'descripcion' => $request->descripcion ?? $denuncia->descripcion,
                'duracion_dias' => $duracionDias,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'restricciones' => $restricciones,
                'notas_admin' => $request->notas_admin,
                'estado' => 'ACTIVA'
            ]);

            // Aplicar restricciones al usuario
            $sancion->aplicarRestricciones();

            $this->crearNotificacionSancion($sancion);

            // Registrar en historial
            HistorialSancion::create([
                'usuario_id' => $denuncia->usuario_denunciado_id,
                'sancion_id' => $sancion->id,
                'accion' => 'CREACION',
                'detalles' => [
                    'tipo' => $sancion->tipo,
                    'nivel' => $sancion->nivel,
                    'duracion_dias' => $sancion->duracion_dias,
                    'denuncia_id' => $denunciaId
                ],
                'realizado_por' => $usuario->id,
                'ip_address' => $request->ip()
            ]);

            // Actualizar denuncia con referencia a la sanción
            $denuncia->update([
                'notas_admin' => $denuncia->notas_admin . "\n\n[Sanción aplicada: {$sancion->tipo} - Nivel: {$sancion->nivel}]"
            ]);

            Log::info('Sanción aplicada:', [
                'sancion_id' => $sancion->id,
                'usuario_id' => $denuncia->usuario_denunciado_id,
                'administrador_id' => $usuario->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sanción aplicada correctamente',
                'data' => $sancion
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al aplicar sanción:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'denuncia_id' => $denunciaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar la sanción'
            ], 500);
        }
    }

    /**
     * Determinar restricciones automáticas
     */
    private function determinarRestriccionesAutomaticas($tipo, $nivel)
    {
        $restricciones = [];

        switch ($tipo) {
            case 'SUSPENSION_TEMPORAL':
                $restricciones = ['ACCESO_PLATAFORMA'];
                break;
                
            case 'LIMITACION_FUNCIONES':
                switch ($nivel) {
                    case 'LEVE':
                        $restricciones = ['PUBLICAR_COMENTARIOS'];
                        break;
                    case 'MODERADO':
                        $restricciones = ['PUBLICAR_COMENTARIOS', 'ENVIAR_MENSAJES'];
                        break;
                    case 'GRAVE':
                        $restricciones = ['CREAR_OFERTAS', 'SOLICITAR_ADOPCION'];
                        break;
                    case 'MUY_GRAVE':
                        $restricciones = ['CREAR_OFERTAS', 'SOLICITAR_ADOPCION', 'SUBIR_MASCOTAS'];
                        break;
                }
                break;
                
            case 'BLOQUEO_TEMPORAL':
            case 'BLOQUEO_PERMANENTE':
                $restricciones = ['ACCESO_PLATAFORMA'];
                break;
        }

        return $restricciones;
    }

    /**
     * Obtener sanciones de un usuario
     */
    public function obtenerSancionesUsuario($usuarioId)
    {
        try {
            /** @var User $usuario */
            $usuario = Auth::user();

            
            // Solo administradores o el propio usuario pueden ver sus sanciones
            if (!$usuario->isAdministrador() && $usuario->id != $usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estas sanciones'
                ], 403);
            }

            $sanciones = Sancion::with(['denuncia', 'usuario'])
                ->where('usuario_id', $usuarioId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Calcular estadísticas
            $estadisticas = [
                'total' => $sanciones->count(),
                'activas' => $sanciones->where('estado', 'ACTIVA')->count(),
                'por_tipo' => $sanciones->groupBy('tipo')->map->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $sanciones,
                'estadisticas' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo sanciones:', [
                'error' => $e->getMessage(),
                'usuario_id' => $usuarioId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sanciones'
            ], 500);
        }
    }

    /**
     * Revocar sanción
     */
    public function revocarSancion($sancionId)
    {
        try {
            /** @var User $usuario */
            $usuario = Auth::user();
            
            if (!$usuario->isAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para revocar sanciones'
                ], 403);
            }

            $sancion = Sancion::findOrFail($sancionId);

            if (!$sancion->estaActiva()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sanción no está activa'
                ], 400);
            }

            $sancion->revocar();

            // Registrar en historial
            HistorialSancion::create([
                'usuario_id' => $sancion->usuario_id,
                'sancion_id' => $sancion->id,
                'accion' => 'REVOCACION',
                'detalles' => [
                    'razon' => 'Revocada por administrador',
                    'fecha_revocacion' => now()->toDateTimeString()
                ],
                'realizado_por' => $usuario->id,
                'ip_address' => request()->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sanción revocada correctamente',
                'data' => $sancion
            ]);

        } catch (\Exception $e) {
            Log::error('Error revocando sanción:', [
                'error' => $e->getMessage(),
                'sancion_id' => $sancionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al revocar la sanción'
            ], 500);
        }
    }

    /**
     * Verificar si usuario tiene restricciones
     */
    public function verificarRestriccionesUsuario($usuarioId)
    {
        try {
            $usuario = User::findOrFail($usuarioId);

            $sancionesActivas = Sancion::activas()
                ->where('usuario_id', $usuarioId)
                ->get();

            $restricciones = [];
            $sancionesInfo = [];

            foreach ($sancionesActivas as $sancion) {
                if ($sancion->restricciones) {
                    $restricciones = array_merge($restricciones, $sancion->restricciones);
                }
                
                $sancionesInfo[] = [
                    'id' => $sancion->id,
                    'tipo' => $sancion->tipo,
                    'razon' => $sancion->razon,
                    'fecha_fin' => $sancion->fecha_fin,
                    'restricciones' => $sancion->restricciones
                ];
            }

            // Eliminar duplicados
            $restricciones = array_unique($restricciones);

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario_id' => $usuarioId,
                    'tiene_sanciones_activas' => $sancionesActivas->count() > 0,
                    'restricciones' => $restricciones,
                    'sanciones' => $sancionesInfo,
                    'estado_usuario' => $usuario->estado
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar restricciones'
            ], 500);
        }
    }

    /**
     * Obtener todas las sanciones (solo administradores)
     */
    public function index(Request $request)
    {
        try {
            /** @var User $usuario */
            $usuario = Auth::user();
            
            if (!$usuario->isAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver todas las sanciones'
                ], 403);
            }

            $query = Sancion::with(['usuario', 'denuncia']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->filled('fecha_desde')) {
                $query->where('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->where('created_at', '<=', $request->fecha_hasta);
            }

            // Ordenar
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $sanciones = $query->paginate($perPage);

            // Estadísticas
            $estadisticas = [
                'total' => Sancion::count(),
                'activas' => Sancion::where('estado', 'ACTIVA')->count(),
                'por_tipo' => Sancion::selectRaw('tipo, COUNT(*) as total')
                    ->groupBy('tipo')
                    ->pluck('total', 'tipo')
                    ->toArray(),
                'por_nivel' => Sancion::selectRaw('nivel, COUNT(*) as total')
                    ->groupBy('nivel')
                    ->pluck('total', 'nivel')
                    ->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => $sanciones,
                'estadisticas' => $estadisticas,
                'filters' => [
                    'tipos' => Sancion::TIPOS,
                    'niveles' => array_keys(Sancion::NIVELES),
                    'estados' => Sancion::ESTADOS
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo sanciones:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las sanciones'
            ], 500);
        }
    }

    /**
     * Obtener recomendación de sanción basada en denuncia
     */
    public function recomendarSancion($denunciaId)
    {
        try {
            /** @var User $usuario */
            $usuario = Auth::user();
            
            if (!$usuario->isAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver recomendaciones'
                ], 403);
            }

            $denuncia = Denuncia::with('usuarioDenunciado')->findOrFail($denunciaId);

            // Obtener historial de denuncias del usuario
            $historialDenuncias = Denuncia::where('usuario_denunciado_id', $denuncia->usuario_denunciado_id)
                ->where('estado', 'resuelta')
                ->count();

            // Obtener sanciones previas
            $sancionesPrevias = Sancion::where('usuario_id', $denuncia->usuario_denunciado_id)
                ->whereIn('estado', ['CUMPLIDA', 'EXPIRADA'])
                ->count();

            // Determinar recomendación basada en gravedad y historial
            $recomendacion = $this->generarRecomendacion(
                $denuncia->categoria,
                $historialDenuncias,
                $sancionesPrevias
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'denuncia' => $denuncia,
                    'historial_denuncias' => $historialDenuncias,
                    'sanciones_previas' => $sancionesPrevias,
                    'recomendacion' => $recomendacion
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando recomendación:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar recomendación'
            ], 500);
        }
    }

    /**
     * Generar recomendación de sanción
     */
    private function generarRecomendacion($categoria, $historialDenuncias, $sancionesPrevias)
    {
        $gravedadBase = $this->determinarGravedadDenuncia($categoria);
        $nivelReincidencia = $this->calcularNivelReincidencia($historialDenuncias, $sancionesPrevias);

        $recomendacion = [
            'tipo' => 'ADVERTENCIA',
            'nivel' => 'LEVE',
            'duracion_dias' => null,
            'restricciones' => [],
            'razon' => 'Primera incidencia'
        ];

        // Ajustar según gravedad y reincidencia
        if ($gravedadBase === 'ALTA' || $nivelReincidencia >= 2) {
            $recomendacion['tipo'] = 'SUSPENSION_TEMPORAL';
            $recomendacion['nivel'] = 'GRAVE';
            $recomendacion['duracion_dias'] = 7;
            $recomendacion['restricciones'] = ['ACCESO_PLATAFORMA'];
            $recomendacion['razon'] = 'Incidente grave o reincidencia';
        } elseif ($gravedadBase === 'MEDIA' || $nivelReincidencia === 1) {
            $recomendacion['tipo'] = 'LIMITACION_FUNCIONES';
            $recomendacion['nivel'] = 'MODERADO';
            $recomendacion['duracion_dias'] = 3;
            $recomendacion['restricciones'] = ['CREAR_OFERTAS', 'SOLICITAR_ADOPCION'];
            $recomendacion['razon'] = 'Incidente moderado o primera reincidencia';
        }

        // Para reincidentes graves
        if ($historialDenuncias >= 3 || $sancionesPrevias >= 2) {
            $recomendacion['tipo'] = 'SUSPENSION_TEMPORAL';
            $recomendacion['nivel'] = 'MUY_GRAVE';
            $recomendacion['duracion_dias'] = 30;
            $recomendacion['restricciones'] = ['ACCESO_PLATAFORMA'];
            $recomendacion['razon'] = 'Reincidencia grave';
        }

        return $recomendacion;
    }

    private function determinarGravedadDenuncia($categoria)
    {
        $gravedades = [
            'Maltrato Animal' => 'ALTA',
            'Mascota ilegal' => 'ALTA',
            'Estafa o uso comercial' => 'MEDIA',
            'Perfil falso' => 'MEDIA',
            'Contenido inapropiado' => 'BAJA'
        ];

        return $gravedades[$categoria] ?? 'MEDIA';
    }

    private function calcularNivelReincidencia($historialDenuncias, $sancionesPrevias)
    {
        if ($sancionesPrevias >= 2) return 3; // Reincidente crónico
        if ($sancionesPrevias === 1) return 2; // Reincidente
        if ($historialDenuncias >= 2) return 1; // Primera reincidencia
        return 0; // Primera vez
    }

   /**
    * Crear notificación para sanción aplicada (SOLO para advertencias)
    */
    private function crearNotificacionSancion($sancion)
    {
        try {
            // Solo crear notificación para advertencias
            if ($sancion->tipo === 'ADVERTENCIA') {
                // Usar el método estático del modelo Notificacion
                Notificacion::crearParaAdvertenciaSancion($sancion);
                
                Log::info('Notificación creada para advertencia:', [
                    'sancion_id' => $sancion->id,
                    'usuario_id' => $sancion->usuario_id
                ]);
            } else {
                Log::info('No se crea notificación para tipo de sanción:', [
                    'tipo' => $sancion->tipo,
                    'sancion_id' => $sancion->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al crear notificación de sanción:', [
                'error' => $e->getMessage(),
                'sancion_id' => $sancion->id
            ]);
            // No interrumpir el flujo principal por error en notificación
        }
    }

    /**
     * Obtener información de sanción activa del usuario autenticado
     */
    public function obtenerSancionActivaUsuario(Request $request)
    {
        try {
            /** @var User $usuario */
            $usuario = Auth::user();
            
            // Obtener sanciones activas
            $sancionesActivas = Sancion::activas()
                ->where('usuario_id', $usuario->id)
                ->orderBy('fecha_inicio', 'desc')
                ->get();
            
            if ($sancionesActivas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay sanciones activas'
                ], 404);
            }
            
            // Tomar la sanción más reciente
            $sancion = $sancionesActivas->first();
            
            // Formatear respuesta
            $data = [
                'id' => $sancion->id,
                'tipo' => $sancion->tipo,
                'razon' => $sancion->razon,
                'descripcion' => $sancion->descripcion,
                'fecha_inicio' => $sancion->fecha_inicio,
                'fecha_fin' => $sancion->fecha_fin,
                'duracion_dias' => $sancion->duracion_dias,
                'es_permanente' => $sancion->tipo === 'BLOQUEO_PERMANENTE' || is_null($sancion->fecha_fin),
                'puede_apelar' => $sancion->tipo !== 'BLOQUEO_PERMANENTE' && $sancion->estado === 'ACTIVA',
                'restricciones' => $sancion->restricciones
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Sanción activa encontrada',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo sanción activa:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de sanción'
            ], 500);
        }
    }

     public function obtenerSancionActivaDetallada(Request $request)
    {
        try {
            Log::info('🔍 Iniciando obtención de sanción activa detallada', [
                'user_id' => Auth::id(),
                'user' => Auth::user() ? Auth::user()->email : 'No autenticado'
            ]);
            
            /** @var User $usuario */
            $usuario = Auth::user();
            
            if (!$usuario) {
                Log::warning('⚠️ Usuario no autenticado en obtenerSancionActivaDetallada');
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            Log::info('👤 Usuario autenticado', [
                'usuario_id' => $usuario->id,
                'email' => $usuario->email,
                'estado' => $usuario->estado
            ]);
            
            // Obtener sanciones activas
            $sancionesActivas = Sancion::activas()
                ->where('usuario_id', $usuario->id)
                ->orderBy('fecha_inicio', 'desc')
                ->get();
            
            Log::info('📋 Sanciones activas encontradas:', [
                'total' => $sancionesActivas->count(),
                'sanciones' => $sancionesActivas->pluck('id')
            ]);
            
            if ($sancionesActivas->isEmpty()) {
                Log::info('ℹ️ No hay sanciones activas para el usuario', ['usuario_id' => $usuario->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'No hay sanciones activas'
                ], 404);
            }
            
            // Tomar la sanción más reciente
            $sancion = $sancionesActivas->first();
            
            Log::info('🎯 Sanción seleccionada:', [
                'sancion_id' => $sancion->id,
                'tipo' => $sancion->tipo,
                'estado' => $sancion->estado,
                'fecha_inicio' => $sancion->fecha_inicio,
                'fecha_fin' => $sancion->fecha_fin
            ]);
            
            // Calcular días restantes
            $diasRestantes = null;
            if ($sancion->fecha_fin) {
                $hoy = now();
                $fin = $sancion->fecha_fin;
                $diasRestantes = $hoy->diffInDays($fin, false);
                Log::info('📅 Cálculo de días restantes:', [
                    'hoy' => $hoy,
                    'fin' => $fin,
                    'dias_restantes' => $diasRestantes
                ]);
                
                if ($diasRestantes < 0) {
                    $diasRestantes = 0;
                    Log::info('⏰ Sanción expirada, verificando...');
                    $sancion->verificarExpiracion();
                }
            }
            
            // Formatear respuesta con más detalles
            $data = [
                'id' => $sancion->id,
                'tipo' => $sancion->tipo,
                'nivel' => $sancion->nivel,
                'razon' => $sancion->razon,
                'descripcion' => $sancion->descripcion,
                'fecha_inicio' => $sancion->fecha_inicio ? $sancion->fecha_inicio->toDateTimeString() : null,
                'fecha_fin' => $sancion->fecha_fin ? $sancion->fecha_fin->toDateTimeString() : null,
                'duracion_dias' => $sancion->duracion_dias,
                'es_permanente' => $sancion->tipo === 'BLOQUEO_PERMANENTE' || is_null($sancion->fecha_fin),
                'puede_apelar' => $sancion->tipo !== 'BLOQUEO_PERMANENTE' && $sancion->estado === 'ACTIVA',
                'estado' => $sancion->estado,
                'restricciones' => $sancion->restricciones ?? [],
                'dias_restantes' => $diasRestantes,
                'notas_admin' => $sancion->notas_admin,
                'created_at' => $sancion->created_at ? $sancion->created_at->toDateTimeString() : null,
                'updated_at' => $sancion->updated_at ? $sancion->updated_at->toDateTimeString() : null
            ];
            
            Log::info('✅ Sanción activa encontrada y formateada', [
                'sancion_id' => $sancion->id,
                'data_keys' => array_keys($data)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sanción activa encontrada',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo sanción activa detallada:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_url' => $request->fullUrl(),
                'request_method' => $request->method()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información detallada de sanción: ' . $e->getMessage()
            ], 500);
        }
    }
}