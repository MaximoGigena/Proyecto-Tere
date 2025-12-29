<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Denuncia;
use App\Models\Mascota;
use App\Models\OfertaAdopcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DenunciaController extends Controller
{
    /**
     * Crear una nueva denuncia
     */
    public function store(Request $request)
    {
        // Validación personalizada para oferta_id
        $validator = Validator::make($request->all(), [
            'mascota_id' => 'nullable|exists:mascotas,id',
            'oferta_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!OfertaAdopcion::where('id_oferta', $value)->exists()) {
                        $fail('La oferta seleccionada no existe.');
                    }
                }
            ],
            'categoria' => 'required|string|in:' . implode(',', Denuncia::CATEGORIAS),
            'subcategoria' => 'required|string',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'mascota_id.exists' => 'La mascota seleccionada no existe.',
            'categoria.in' => 'La categoría seleccionada no es válida.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Obtener usuario autenticado
            $usuario = Auth::user();
            
            Log::info('Usuario autenticado:', ['usuario_id' => $usuario->id]);
            Log::info('Datos recibidos:', $request->all());

            // Determinar mascota y usuario denunciado
            $mascota = null;
            $usuarioDenunciado = null;
            $oferta = null;

            if ($request->filled('mascota_id')) {
                Log::info('Buscando mascota con ID:', ['mascota_id' => $request->mascota_id]);
                $mascota = Mascota::find($request->mascota_id);
                
                if (!$mascota) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La mascota no existe'
                    ], 404);
                }
                
                $usuarioDenunciado = $mascota->usuario;
                Log::info('Mascota encontrada:', ['mascota_id' => $mascota->id, 'usuario_denunciado' => $usuarioDenunciado?->id]);
                
            } elseif ($request->filled('oferta_id')) {
                Log::info('Buscando oferta con ID:', ['oferta_id' => $request->oferta_id]);
                
                // Buscar la oferta
                $oferta = OfertaAdopcion::where('id_oferta', $request->oferta_id)
                    ->with('mascota')
                    ->first();
                
                if (!$oferta) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La oferta no existe'
                    ], 404);
                }
                
                Log::info('Oferta encontrada:', [
                    'id_oferta' => $oferta->id_oferta,
                    'id_mascota' => $oferta->id_mascota
                ]);
                
                $mascota = $oferta->mascota;
                $usuarioDenunciado = $mascota->usuario;
                Log::info('Mascota de la oferta:', ['mascota_id' => $mascota->id]);
                
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar mascota_id u oferta_id'
                ], 400);
            }

            // Verificar que no se está denunciando a uno mismo
            if ($usuarioDenunciado && $usuarioDenunciado->id === $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes denunciar tu propia mascota'
                ], 400);
            }

            // Verificar si ya existe una denuncia pendiente similar
            $denunciaExistente = Denuncia::where('usuario_id', $usuario->id)
                ->where('mascota_id', $mascota->id)
                ->where('categoria', $request->categoria)
                ->where('estado', 'pendiente')
                ->first();

            if ($denunciaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has realizado una denuncia similar para esta mascota'
                ], 400);
            }

            // Crear la denuncia
            $denunciaData = [
                'usuario_id' => $usuario->id,
                'mascota_id' => $mascota->id,
                'usuario_denunciado_id' => $usuarioDenunciado?->id,
                'categoria' => $request->categoria,
                'subcategoria' => $request->subcategoria,
                'descripcion' => $request->descripcion,
                'estado' => 'pendiente'
            ];

            // CAMBIO AQUÍ: 'oferta_id' → 'id_oferta'
            if ($oferta) {
                $denunciaData['id_oferta'] = $oferta->id_oferta;  // CAMBIADO
            }

            Log::info('Creando denuncia con datos:', $denunciaData);
            
            $denuncia = Denuncia::create($denunciaData);

            Log::info('Denuncia creada exitosamente:', ['denuncia_id' => $denuncia->id]);

            return response()->json([
                'success' => true,
                'message' => 'Denuncia registrada correctamente. Se revisará en breve.',
                'data' => $denuncia
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear denuncia:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la denuncia',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener las categorías y subcategorías de denuncias
     */
    public function categorias()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'categorias' => Denuncia::CATEGORIAS,
                'subcategorias' => Denuncia::SUBCATEGORIAS
            ]
        ]);
    }

    /**
     * Verificar si ya existe denuncia pendiente para una mascota
     */
    public function verificarDenuncia(Request $request)
    {
        $request->validate([
            'mascota_id' => 'required|exists:mascotas,id'
        ]);

        $usuario = Auth::user();
        $denunciaExistente = Denuncia::where('usuario_id', $usuario->id)
            ->where('mascota_id', $request->mascota_id)
            ->where('estado', 'pendiente')
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'tiene_denuncia_pendiente' => $denunciaExistente
            ]
        ]);
    }

    /**
     * Obtener todas las denuncias (con permisos diferentes según rol)
     */
    public function index(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            Log::info('=== DENUNCIAS INDEX - Usuario:', [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
                'userable_type' => $usuario->userable_type,
                'es_admin' => $usuario->isAdministrador()
            ]);
            
            // Consulta base con relaciones
            $query = Denuncia::with([
                'mascota',
                'usuarioDenunciado',
                'oferta.mascota',
                'usuario' // Agregamos relación con usuario que hizo la denuncia
            ]);
            
            // ⚡ IMPORTANTE: Si NO es administrador, solo ver sus propias denuncias
            if (!$usuario->isAdministrador()) {
                $query->where('usuario_id', $usuario->id);
                Log::info('Usuario normal - Filtrando solo sus denuncias');
            } else {
                Log::info('Administrador - Viendo TODAS las denuncias');
            }
            
            // Filtrar por categoría si se proporciona
            if ($request->filled('categoria')) {
                $query->where('categoria', $request->categoria);
                Log::info('Filtro categoría aplicado:', ['categoria' => $request->categoria]);
            }
            
            // Filtrar por estado si se proporciona
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
                Log::info('Filtro estado aplicado:', ['estado' => $request->estado]);
            }
            
            // Ordenar
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');
            
            $query->orderBy($orden, $direccion);
            
            // Obtener resultados (con paginación opcional)
            $perPage = $request->get('per_page', 15);
            $denuncias = $request->has('paginate') 
                ? $query->paginate($perPage)
                : $query->get();
            
            Log::info('Denuncias encontradas:', [
                'total' => $denuncias->count(),
                'es_administrador' => $usuario->isAdministrador()
            ]);
            
            // Formatear respuesta con información adicional para administradores
            $denunciasFormateadas = $denuncias->map(function ($denuncia) use ($usuario) {
                return $this->formatearDenuncia($denuncia, $usuario->isAdministrador());
            });
            
            // Estadísticas para administradores
            $estadisticas = [];
            if ($usuario->isAdministrador()) {
                $estadisticas = [
                    'total_denuncias' => Denuncia::count(),
                    'por_estado' => Denuncia::selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->pluck('total', 'estado')
                        ->toArray(),
                    'por_categoria' => Denuncia::selectRaw('categoria, COUNT(*) as total')
                        ->groupBy('categoria')
                        ->pluck('total', 'categoria')
                        ->toArray()
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $denunciasFormateadas,
                'total' => $denuncias->count(),
                'es_administrador' => $usuario->isAdministrador(),
                'estadisticas' => $estadisticas,
                'filters' => [
                    'categorias' => Denuncia::CATEGORIAS,
                    'estados' => ['pendiente', 'en_revision', 'resuelta', 'descarta']
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo denuncias:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las denuncias',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Obtener una denuncia específica
     */
    public function show($id)
    {
        try {
            $usuario = Auth::user();
            
            $query = Denuncia::with([
                'mascota',
                'usuarioDenunciado',
                'oferta.mascota',
                'usuario' // Agregar usuario que hizo la denuncia
            ]);
            
            // Si NO es administrador, solo puede ver sus propias denuncias
            if (!$usuario->isAdministrador()) {
                $query->where('usuario_id', $usuario->id);
            }
            
            $denuncia = $query->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $this->formatearDenunciaDetalle($denuncia, $usuario->isAdministrador())
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Denuncia no encontrada o no tienes permisos para verla'
            ], 404);
        }
    }
    
    /**
     * Formatear denuncia para lista
     */
    private function formatearDenuncia($denuncia, $esAdministrador = false)
    {
        $formateado = [
            'id' => $denuncia->id,
            'denunciante_id' => $denuncia->usuario_id,
            'denunciado_id' => $denuncia->usuario_denunciado_id,
            'razon' => $denuncia->categoria,
            'subrazon' => $denuncia->subcategoria,
            'descripcion' => $denuncia->descripcion,
            'descripcionDenunciante' => $denuncia->descripcion,
            'gravedad' => $this->determinarGravedad($denuncia->categoria),
            'fecha' => $denuncia->created_at->toDateString(),
            'fecha_completa' => $denuncia->created_at->format('Y-m-d H:i:s'),
            'estado' => $denuncia->estado,
            // Información de la mascota
            'mascota' => $denuncia->mascota ? [
                'id' => $denuncia->mascota->id,
                'nombre' => $denuncia->mascota->nombre,
                'especie' => $denuncia->mascota->especie,
                'foto' => $denuncia->mascota->foto_principal ?? null
            ] : null,
            // Información del usuario denunciado
            'usuario_denunciado' => $denuncia->usuarioDenunciado ? [
                'id' => $denuncia->usuarioDenunciado->id,
                'nombre' => $denuncia->usuarioDenunciado->name ?? $denuncia->usuarioDenunciado->nombre,
                'email' => $denuncia->usuarioDenunciado->email
            ] : null
        ];
        
        // Si es administrador, agregar información del usuario que hizo la denuncia
        if ($esAdministrador && $denuncia->usuario) {
            $formateado['usuario_denunciante'] = [
                'id' => $denuncia->usuario->id,
                'nombre' => $denuncia->usuario->name ?? $denuncia->usuario->nombre,
                'email' => $denuncia->usuario->email,
                'userable_type' => $denuncia->usuario->userable_type
            ];
        }
        
        return $formateado;
    }
    
    /**
     * Formatear denuncia para detalle
     */
    private function formatearDenunciaDetalle($denuncia, $esAdministrador = false)
    {
        $data = $this->formatearDenuncia($denuncia, $esAdministrador);
        
        // Agregar información adicional para el detalle
        $data['detalles'] = [
            'categoria' => $denuncia->categoria,
            'subcategoria' => $denuncia->subcategoria,
            'descripcion_completa' => $denuncia->descripcion,
            'notas_admin' => $denuncia->notas_admin,
            'resuelta_en' => $denuncia->resuelta_en?->format('Y-m-d H:i:s'),
            'creada' => $denuncia->created_at->format('Y-m-d H:i:s'),
            'actualizada' => $denuncia->updated_at->format('Y-m-d H:i:s')
        ];
        
        // Información de la oferta si existe
        if ($denuncia->oferta) {
            $data['oferta'] = [
                'id_oferta' => $denuncia->oferta->id_oferta,
                'estado_oferta' => $denuncia->oferta->estado_oferta,
                'permiso_historial_medico' => $denuncia->oferta->permiso_historial_medico,
                'permiso_contacto_tutor' => $denuncia->oferta->permiso_contacto_tutor
            ];
        }
        
        // Información extra para administradores
        if ($esAdministrador && $denuncia->usuario) {
            $data['informacion_administrativa'] = [
                'ip_registro' => $denuncia->getAttribute('ip_address') ?? 'No disponible',
                'user_agent' => $denuncia->getAttribute('user_agent') ?? 'No disponible',
                'denunciante_verificado' => $denuncia->usuario->email_verified_at ? true : false
            ];
        }
        
        return $data;
    }
    
    /**
     * Determinar gravedad basada en categoría
     */
    private function determinarGravedad($categoria)
    {
        $gravedades = [
            'Maltrato Animal' => 'Alta',
            'Mascota ilegal' => 'Alta',
            'Estafa o uso comercial' => 'Media',
            'Perfil falso' => 'Media',
            'Contenido inapropiado' => 'Baja'
        ];
        
        return $gravedades[$categoria] ?? 'Media';
    }

    /**
     * Actualizar estado de una denuncia (solo administradores)
     */
    public function actualizarEstado(Request $request, $id)
    {
        try {
            $usuario = Auth::user();
            
            if (!$usuario->isAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }
            
            $request->validate([
                'estado' => 'required|in:pending,en_revision,resuelta,descarta',
                'notas_admin' => 'nullable|string|max:1000'
            ]);
            
            $denuncia = Denuncia::findOrFail($id);
            
            $denuncia->update([
                'estado' => $request->estado,
                'notas_admin' => $request->notas_admin ?? $denuncia->notas_admin,
                'resuelta_en' => $request->estado === 'resuelta' ? now() : null
            ]);
            
            // Opcional: Notificar al usuario que hizo la denuncia
            
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => $denuncia
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando estado de denuncia:', [
                'error' => $e->getMessage(),
                'denuncia_id' => $id,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado'
            ], 500);
        }
    }

    /**
     * Agregar notas del administrador
     */
    public function actualizarNotas(Request $request, $id)
    {
        try {
            $usuario = Auth::user();
            
            if (!$usuario->isAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }
            
            $request->validate([
                'notas_admin' => 'required|string|max:2000'
            ]);
            
            $denuncia = Denuncia::findOrFail($id);
            
            $denuncia->update([
                'notas_admin' => $request->notas_admin
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notas actualizadas correctamente',
                'data' => $denuncia
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando notas de denuncia:', [
                'error' => $e->getMessage(),
                'denuncia_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar las notas'
            ], 500);
        }
    }
}