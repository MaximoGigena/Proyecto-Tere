<?php
// app/Http/Controllers/Api/VeterinarioProcedimientoController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProcesoMedico;
use App\Models\User;
use App\Models\Veterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VeterinarioProcedimientoController extends Controller
{
    public function misProcedimientos(Request $request)
    {
        Log::info('🔍 VeterinarioProcedimientoController::misProcedimientos - Iniciando');
        
        // Verificar autenticación
        if (!Auth::check()) {
            Log::warning('❌ Usuario no autenticado');
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }
        
        $user = Auth::user();
        
        // Verificar que es veterinario
        if ($user->userable_type !== 'App\\Models\\Veterinario') {
            Log::warning('❌ Usuario no es veterinario');
            return response()->json([
                'success' => false,
                'message' => 'Esta funcionalidad es solo para veterinarios'
            ], 403);
        }
        
        // Obtener el modelo Veterinario
        $veterinario = $user->userable;
        
        if (!$veterinario) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar información del veterinario'
            ], 500);
        }
        
        // Consulta de procedimientos con relaciones necesarias
        try {
            $query = ProcesoMedico::with([
                'mascota:id,nombre,especie,fecha_nacimiento,sexo,castrado',
                'mascota.fotos', // Cargar fotos de la mascota
                'centroVeterinario:id,nombre',
                'procesable',
                'farmacosAsociados'
            ])
            ->where('veterinario_id', $user->id);
            
            // Aplicar filtros desde request
            if ($request->has('categoria') && $request->categoria) {
                $query->where('categoria', $request->categoria);
            }
            
            if ($request->has('fecha_desde') && $request->fecha_desde) {
                $query->whereDate('fecha_aplicacion', '>=', $request->fecha_desde);
            }
            
            if ($request->has('fecha_hasta') && $request->fecha_hasta) {
                $query->whereDate('fecha_aplicacion', '<=', $request->fecha_hasta);
            }
            
            if ($request->has('busqueda') && $request->busqueda) {
                $busqueda = $request->busqueda;
                $query->where(function($q) use ($busqueda) {
                    $q->where('observaciones', 'like', "%{$busqueda}%")
                      ->orWhereHas('mascota', function($q2) use ($busqueda) {
                          $q2->where('nombre', 'like', "%{$busqueda}%");
                      })
                      ->orWhereHas('procesable', function($q3) use ($busqueda) {
                          $q3->where('nombre', 'like', "%{$busqueda}%");
                      });
                });
            }
            
            // Ordenar por fecha más reciente primero
            $procedimientos = $query->orderBy('fecha_aplicacion', 'desc')
                                   ->get();
            
            // Formatear respuesta
            $procedimientosFormateados = $procedimientos->map(function($procedimiento) {
                // Obtener imagen de la mascota
                $fotoPrincipal = null;
                if ($procedimiento->mascota && $procedimiento->mascota->fotos) {
                    $fotoPrincipalFoto = $procedimiento->mascota->fotos
                        ->where('es_principal', true)
                        ->first() ?? $procedimiento->mascota->fotos->first();
                    
                    if ($fotoPrincipalFoto) {
                        $fotoPrincipal = asset('storage/' . $fotoPrincipalFoto->ruta_foto);
                    }
                }
                
                // Obtener tipo de procedimiento detallado
                $tipoProcedimiento = $this->getTipoProcedimientoDetallado($procedimiento);
                
                return [
                    'id' => $procedimiento->id,
                    'categoria' => $procedimiento->categoria,
                    'tipo_procedimiento' => $tipoProcedimiento, // Añadido
                    'nombre_procedimiento' => $tipoProcedimiento, // Nombre con tipo
                    'fecha_aplicacion' => $procedimiento->fecha_aplicacion,
                    'fecha_formateada' => $procedimiento->fecha_aplicacion->format('d/m/Y H:i'), // Añadido
                    'observaciones' => $procedimiento->observaciones,
                    'costo' => $procedimiento->costo,
                    'mascota' => $procedimiento->mascota ? [
                        'id' => $procedimiento->mascota->id,
                        'nombre' => $procedimiento->mascota->nombre,
                        'especie' => $procedimiento->mascota->especie,
                        'fecha_nacimiento' => $procedimiento->mascota->fecha_nacimiento,
                        'edad' => $procedimiento->mascota->edad_formateada, // Usar accessor
                        'sexo' => $procedimiento->mascota->sexo,
                        'castrado' => $procedimiento->mascota->castrado,
                        'foto_url' => $fotoPrincipal, // Añadido imagen de la mascota
                        'foto_thumbnail' => $fotoPrincipal ? $this->getThumbnailUrl($fotoPrincipal) : null // Opcional: thumbnail
                    ] : null,
                    'centro_veterinario' => $procedimiento->centroVeterinario ? [
                        'id' => $procedimiento->centroVeterinario->id,
                        'nombre' => $procedimiento->centroVeterinario->nombre,
                        'direccion' => $procedimiento->centroVeterinario->direccion ?? null,
                        'telefono' => $procedimiento->centroVeterinario->telefono ?? null
                    ] : null,
                    'procesable' => $procedimiento->procesable ? [
                        'nombre' => $procedimiento->procesable->nombre ?? 'Sin nombre',
                        'descripcion' => $procedimiento->procesable->descripcion ?? 'Sin descripción',
                        'tipo' => class_basename($procedimiento->procesable), // Añadido: Vacuna, Cirugia, etc.
                        // Campos específicos según tipo
                        'detalles' => $this->getDetallesProcesable($procedimiento->procesable)
                    ] : null,
                    'farmacosAsociados' => $procedimiento->farmacosAsociados->map(function($farmaco) {
                        return [
                            'nombre' => $farmaco->nombre,
                            'dosis' => $farmaco->dosis,
                            'frecuencia' => $farmaco->frecuencia,
                            'via_administracion' => $farmaco->via_administracion ?? null,
                            'duracion' => $farmaco->duracion ?? null
                        ];
                    }),
                    'created_at' => $procedimiento->created_at,
                    'updated_at' => $procedimiento->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Procedimientos obtenidos exitosamente',
                'data' => $procedimientosFormateados,
                'total' => $procedimientos->count(),
                'veterinario' => [
                    'id' => $veterinario->id,
                    'nombre_completo' => $veterinario->nombre_completo,
                    'matricula' => $veterinario->matricula,
                    'especialidad' => $veterinario->especialidad
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener procedimientos:', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los procedimientos',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Obtener detalles completos de un procedimiento específico
     * RUTA: GET /api/veterinario/procedimientos/{id}/detalles
     */
    public function detallesProcedimiento($id)
    {
        Log::info('🔍 VeterinarioProcedimientoController::detallesProcedimiento - Iniciando', ['id' => $id]);
        
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }
        
        $user = Auth::user();
        
        if ($user->userable_type !== 'App\\Models\\Veterinario') {
            return response()->json([
                'success' => false,
                'message' => 'Esta funcionalidad es solo para veterinarios'
            ], 403);
        }
        
        try {
            // Cargar todas las relaciones necesarias
            $procedimiento = ProcesoMedico::with([
                'mascota:id,nombre,especie,fecha_nacimiento,sexo,castrado,usuario_id',
                'mascota.fotos',
                'mascota.caracteristicas',
                'mascota.usuario:id,nombre', // Solo 'nombre' ya que Usuario no tiene email/telefono
                'mascota.usuario.user:id,email,userable_id,userable_type', // Agregar relación a User para email
                'mascota.usuario.contacto:usuario_id,telefono,email as contacto_email', // Agregar relación a contacto
                'centroVeterinario:id,nombre,direccion,telefono,email',
                'procesable',
                'farmacosAsociados',
                'veterinario:id,name,email',
                'veterinario.userable'
            ])
            ->where('id', $id)
            ->where('veterinario_id', $user->id)
            ->first();
            
            if (!$procedimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procedimiento no encontrado o no autorizado'
                ], 404);
            }
            
            // Obtener imagen de la mascota
            $fotosMascota = [];
            if ($procedimiento->mascota && $procedimiento->mascota->fotos) {
                $fotosMascota = $procedimiento->mascota->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => asset('storage/' . $foto->ruta_foto),
                        'es_principal' => $foto->es_principal,
                        'descripcion' => $foto->descripcion ?? null
                    ];
                });
            }
            
            // Detalles del procedimiento
            $detallesProcedimiento = $this->getDetallesCompletosProcesable($procedimiento->procesable);
            
            $respuesta = [
                'id' => $procedimiento->id,
                'categoria' => $procedimiento->categoria,
                'tipo_procedimiento' => $this->getTipoProcedimientoDetallado($procedimiento),
                'fecha_aplicacion' => $procedimiento->fecha_aplicacion,
                'fecha_formateada' => $procedimiento->fecha_aplicacion->format('d/m/Y H:i'),
                'observaciones' => $procedimiento->observaciones,
                'costo' => $procedimiento->costo,
                'estado' => $procedimiento->estado ?? 'completado',
                
                // Mascota con todos sus datos
                'mascota' => $procedimiento->mascota ? [
                    'id' => $procedimiento->mascota->id,
                    'nombre' => $procedimiento->mascota->nombre,
                    'especie' => $procedimiento->mascota->especie,
                    'raza' => $procedimiento->mascota->caracteristicas->raza ?? 'No especificada',
                    'fecha_nacimiento' => $procedimiento->mascota->fecha_nacimiento,
                    'edad' => $procedimiento->mascota->edad_formateada,
                    'sexo' => $procedimiento->mascota->sexo,
                    'castrado' => $procedimiento->mascota->castrado ? 'Sí' : 'No',
                    'peso' => $procedimiento->mascota->caracteristicas->peso ?? 'No registrado',
                    'color' => $procedimiento->mascota->caracteristicas->color ?? 'No especificado',
                    'fotos' => $fotosMascota,
                    'tutor' => $procedimiento->mascota->usuario ? [
                        'id' => $procedimiento->mascota->usuario->id,
                        'nombre' => $procedimiento->mascota->usuario->nombre,
                        // Obtener email desde la relación User
                        'email' => $procedimiento->mascota->usuario->user->email ?? null,
                        // Obtener teléfono desde la relación contacto
                        'telefono' => $procedimiento->mascota->usuario->contacto->telefono ?? null
                    ] : null,
                ] : null,
                
                // Centro veterinario
                'centro_veterinario' => $procedimiento->centroVeterinario ? [
                    'id' => $procedimiento->centroVeterinario->id,
                    'nombre' => $procedimiento->centroVeterinario->nombre,
                    'direccion' => $procedimiento->centroVeterinario->direccion,
                    'telefono' => $procedimiento->centroVeterinario->telefono,
                    'email' => $procedimiento->centroVeterinario->email
                ] : null,
                
                // Procedimiento específico
                'procedimiento_especifico' => $procedimiento->procesable ? [
                    'tipo' => class_basename($procedimiento->procesable),
                    'nombre' => $procedimiento->procesable->nombre ?? 'Sin nombre',
                    'descripcion' => $procedimiento->procesable->descripcion ?? 'Sin descripción',
                    'detalles' => $detallesProcedimiento
                ] : null,
                
                // Fármacos
                'farmacos' => $procedimiento->farmacosAsociados->map(function($farmaco) {
                    return [
                        'id' => $farmaco->id,
                        'nombre' => $farmaco->nombre,
                        'dosis' => $farmaco->dosis,
                        'frecuencia' => $farmaco->frecuencia,
                        'via_administracion' => $farmaco->via_administracion ?? 'No especificada',
                        'duracion' => $farmaco->duracion ?? 'No especificada',
                        'observaciones' => $farmaco->observaciones ?? null
                    ];
                }),
                
                // Veterinario que realizó el procedimiento
                'veterinario' => $procedimiento->veterinario ? [
                    'id' => $procedimiento->veterinario->id,
                    'nombre' => $procedimiento->veterinario->name ?? $procedimiento->veterinario->email,
                    'email' => $procedimiento->veterinario->email,
                    'matricula' => $procedimiento->veterinario->userable->matricula ?? null,
                    'especialidad' => $procedimiento->veterinario->userable->especialidad ?? null
                ] : null,
                
                'created_at' => $procedimiento->created_at,
                'updated_at' => $procedimiento->updated_at
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Detalles del procedimiento obtenidos exitosamente',
                'data' => $respuesta
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener detalles del procedimiento:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los detalles del procedimiento',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Método auxiliar para obtener el tipo de procedimiento detallado
     */
    private function getTipoProcedimientoDetallado($procedimiento)
    {
        $base = ucfirst($procedimiento->categoria);
        
        if ($procedimiento->procesable) {
            $tipo = class_basename($procedimiento->procesable);
            
            // Personalizar según el tipo
            switch ($tipo) {
                case 'Vacuna':
                    $nombre = $procedimiento->procesable->nombre ?? 'Vacuna';
                    return "{$base} - {$nombre}";
                    
                case 'Cirugia':
                    $tipoCirugia = $procedimiento->procesable->tipo ?? 'Cirugía';
                    return "{$base} - {$tipoCirugia}";
                    
                case 'Desparasitacion':
                    return "{$base} - Desparasitación";
                    
                case 'AnalisisClinico':
                    return "{$base} - Análisis Clínico";
                    
                case 'Tratamiento':
                    $nombre = $procedimiento->procesable->nombre ?? 'Tratamiento';
                    return "{$base} - {$nombre}";
                    
                default:
                    return "{$base} - {$tipo}";
            }
        }
        
        return $base;
    }
    
    /**
     * Método auxiliar para obtener detalles del procesable
     */
    private function getDetallesProcesable($procesable)
    {
        if (!$procesable) {
            return [];
        }
        
        $tipo = class_basename($procesable);
        $detalles = [];
        
        switch ($tipo) {
            case 'Vacuna':
                $detalles = [
                    'lote' => $procesable->lote ?? null,
                    'fecha_vencimiento' => $procesable->fecha_vencimiento ?? null,
                    'fabricante' => $procesable->fabricante ?? null,
                    'via_administracion' => $procesable->via_administracion ?? null
                ];
                break;
                
            case 'Cirugia':
                $detalles = [
                    'tipo' => $procesable->tipo ?? null,
                    'duracion' => $procesable->duracion ?? null,
                    'anestesia' => $procesable->anestesia ?? null,
                    'complicaciones' => $procesable->complicaciones ?? null
                ];
                break;
                
            case 'Desparasitacion':
                $detalles = [
                    'tipo' => $procesable->tipo ?? null,
                    'producto' => $procesable->producto ?? null,
                    'dosis' => $procesable->dosis ?? null,
                    'via_administracion' => $procesable->via_administracion ?? null
                ];
                break;
                
            case 'AnalisisClinico':
                $detalles = [
                    'tipo_analisis' => $procesable->tipo_analisis ?? null,
                    'resultados' => $procesable->resultados ?? null,
                    'laboratorio' => $procesable->laboratorio ?? null
                ];
                break;
                
            case 'Tratamiento':
                $detalles = [
                    'duracion' => $procesable->duracion ?? null,
                    'frecuencia' => $procesable->frecuencia ?? null,
                    'objetivo' => $procesable->objetivo ?? null
                ];
                break;
        }
        
        // Filtrar valores nulos
        return array_filter($detalles);
    }
    
    /**
     * Método auxiliar para obtener detalles completos del procesable
     */
    private function getDetallesCompletosProcesable($procesable)
    {
        if (!$procesable) {
            return [];
        }
        
        // Convertir a array y eliminar campos no deseados
        $detalles = $procesable->toArray();
        
        // Campos a eliminar
        $camposEliminar = [
            'id', 'created_at', 'updated_at', 'deleted_at',
            'proceso_medico_id', 'procesable_type', 'procesable_id'
        ];
        
        foreach ($camposEliminar as $campo) {
            unset($detalles[$campo]);
        }
        
        // Formatear fechas
        foreach ($detalles as $key => $value) {
            if (strpos($key, 'fecha') !== false && $value) {
                try {
                    $detalles[$key . '_formateada'] = \Carbon\Carbon::parse($value)->format('d/m/Y');
                } catch (\Exception $e) {
                    // Si no se puede parsear, dejar el valor original
                }
            }
        }
        
        return $detalles;
    }
    
    /**
     * Método auxiliar para obtener URL de thumbnail (opcional)
     */
    private function getThumbnailUrl($url)
    {
        // Si usas intervention/image o similar para thumbnails
        // return str_replace('/storage/', '/storage/thumbnails/', $url);
        
        // Por ahora devolver la misma URL
        return $url;
    }
    
    /**
     * Obtener procedimientos de un veterinario específico (para administradores)
     * RUTA: GET /api/veterinarios/{veterinario}/procedimientos
     */
    public function index(Request $request, User $veterinarioUsuario)
    {
        Log::info('🔍 VeterinarioProcedimientoController::index - Iniciando para veterinario específico');
        
        // Verificar que el usuario es veterinario
        if ($veterinarioUsuario->userable_type !== 'App\\Models\\Veterinario') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario especificado no es veterinario'
            ], 400);
        }
        
        $veterinario = $veterinarioUsuario->userable;
        $veterinarioId = $veterinarioUsuario->id;
        
        $procedimientos = ProcesoMedico::with([
            'mascota:id,nombre,especie',
            'centroVeterinario:id,nombre',
            'procesable',
            'farmacosAsociados'
        ])
        ->where('veterinario_id', $veterinarioId)
        ->orderBy('fecha_aplicacion', 'desc')
        ->get();
        
        return response()->json([
            'success' => true,
            'veterinario' => [
                'id' => $veterinarioUsuario->id,
                'nombre' => $veterinario->nombre_completo ?? $veterinarioUsuario->email,
                'email' => $veterinarioUsuario->email
            ],
            'procedimientos' => $procedimientos,
            'total' => $procedimientos->count()
        ]);
    }
    
    /**
     * Obtener estadísticas del veterinario autenticado
     * RUTA: GET /api/veterinario/estadisticas
     */
    public function estadisticas()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        
        $user = Auth::user();
        
        if ($user->userable_type !== 'App\\Models\\Veterinario') {
            return response()->json(['error' => 'No es veterinario'], 403);
        }
        
        $veterinario = $user->userable;
        $veterinarioId = $user->id;
        
        $estadisticas = ProcesoMedico::selectRaw('
            COUNT(*) as total_procedimientos,
            SUM(costo) as total_ingresos,
            COUNT(DISTINCT mascota_id) as total_pacientes,
            categoria, 
            COUNT(*) as cantidad
        ')
        ->where('veterinario_id', $veterinarioId)
        ->groupBy('categoria')
        ->get();
        
        return response()->json([
            'success' => true,
            'data' => $estadisticas
        ]);
    }
}