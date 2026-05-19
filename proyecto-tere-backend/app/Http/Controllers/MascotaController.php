<?php

namespace App\Http\Controllers;

use App\Models\Mascota;
use App\Models\CaracteristicasMascota;
use App\Models\MascotaFoto; 
use App\Models\EdadMascota;
use App\Models\MotivoBaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MascotaController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Datos recibidos en store:', $request->all());
        Log::info('Castrado recibido:', ['value' => $request->castrado, 'type' => gettype($request->castrado)]);
        Log::info('Todos los campos:', array_keys($request->all()));
        
        // Validar los datos obligatorios
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'especie' => 'required|in:canino,felino,equino,bovino,ave,pez,otro',
            'fecha_nacimiento' => 'required|string|date_format:d/m/Y|before:today',
            'sexo' => 'required|in:macho,hembra',
            'castrado' => 'required|in:0,1,true,false', // Acepta múltiples formatos
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp'
        ]);
        
        Log::info('Datos validados:', $validated);

        $usuario = Auth::user()->userable;

        // Convertir castrado a booleano
        $castrado = filter_var($request->castrado, FILTER_VALIDATE_BOOLEAN);

        // Crear la mascota
        $mascota = Mascota::create([
            'nombre' => $request->nombre,
            'especie' => $request->especie,
            'sexo' => $request->sexo,
            'castrado' => $castrado, // Usar el valor convertido
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'usuario_id' => $usuario->id
        ]);

        Log::info('Mascota creada:', ['id' => $mascota->id, 'castrado' => $mascota->castrado]);

        // Crear las características opcionales si existen
        if ($mascota->id) {
            $caracteristicas = CaracteristicasMascota::create([
                'mascota_id' => $mascota->id,
                'tamano' => $request->tamano,
                'pelaje' => $request->pelaje,
                'alimentacion' => $request->alimentacion,
                'energia' => $request->energia,
                'ejercicio' => $request->ejercicio,
                'comportamiento_animales' => $request->comportamiento_animales,
                'comportamiento_ninos' => $request->comportamiento_ninos,
                'personalidad' => $request->personalidad,
                'descripcion' => $request->descripcion
            ]);
            
            Log::info('Características creadas:', $caracteristicas->toArray());
        }

        // Procesar las fotos
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $index => $foto) {
                if ($foto->isValid()) {
                    $path = $foto->store('mascotas/' . $mascota->id, 'public');

                    MascotaFoto::create([
                        'mascota_id' => $mascota->id,
                        'ruta_foto' => $path,
                        'es_principal' => $index === 0
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Mascota registrada correctamente',
            'mascota' => $mascota->load(['caracteristicas', 'fotos']), 
            'caracteristicas' => $caracteristicas ?? null
        ], 201);
    }

    public function show($id)
    {
        try {
            $mascota = Mascota::with([
                'caracteristicas', 
                'fotos', 
                'usuario'
            ])
            ->where('id', $id)
            ->firstOrFail();

            // Verificar que el usuario autenticado tenga permisos para ver esta mascota
            $usuarioAutenticado = Auth::user();
            
            // Si no es el dueño y no es veterinario, denegar acceso
            if ($mascota->usuario_id != $usuarioAutenticado->userable->id && !$usuarioAutenticado->isVeterinario()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver esta mascota'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mascota->id,
                    'nombre' => $mascota->nombre,
                    'especie' => $mascota->especie,
                    'sexo' => $mascota->sexo,
                    'castrado' => $mascota->castrado,
                    'fecha_nacimiento' => $mascota->fecha_nacimiento,
                    'usuario_id' => $mascota->usuario_id, // ← CRUCIAL: incluir usuario_id
                    'caracteristicas' => $mascota->caracteristicas,
                    'fotos' => $mascota->fotos,
                    'edad' => $mascota->edadRelacion ? [
                        'dias' => $mascota->edadRelacion->dias,
                        'meses' => $mascota->edadRelacion->meses,
                        'años' => $mascota->edadRelacion->años,
                        'edad_formateada' => $mascota->edad_formateada
                    ] : null,
                    'edad_formateada' => $mascota->edad_formateada, // Usar el accessor
                    'usuario' => [
                        'id' => $mascota->usuario->id,
                        'nombre' => $mascota->usuario->nombre
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mascota no encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al obtener mascota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la mascota: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Validar datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'especie' => 'required|in:canino,felino,equino,bovino,ave,pez,otro',
            'fecha_nacimiento' => 'required|string|date_format:d/m/Y|before:today',
            'sexo' => 'required|in:macho,hembra',
            'castrado' => 'required|boolean',
            'nuevas_fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'fotos_eliminar.*' => 'nullable|integer'
        ]);

        $usuario = Auth::user()->userable;

        // Buscar la mascota
        $mascota = Mascota::where('id', $id)
            ->where('usuario_id', $usuario->id)
            ->firstOrFail();

        // Actualizar datos básicos
        $mascota->update([
            'nombre' => $request->nombre,
            'especie' => $request->especie,
            'fecha_nacimiento' => $request->fecha_nacimiento, // Guardar como string dd/mm/yyyy
            'sexo' => $request->sexo,
            'castrado' => $request->castrado,
        ]);

        // Actualizar características
        $caracteristicas = CaracteristicasMascota::updateOrCreate(
            ['mascota_id' => $mascota->id],
            [
                'tamano' => $request->tamano,
                'pelaje' => $request->pelaje,
                'alimentacion' => $request->alimentacion,
                'energia' => $request->energia,
                'ejercicio' => $request->ejercicio,
                'comportamiento_animales' => $request->comportamiento_animales,
                'comportamiento_ninos' => $request->comportamiento_ninos,
                'personalidad' => $request->personalidad,
                'descripcion' => $request->descripcion
            ]
        );

        // Eliminar fotos marcadas para eliminación
        if ($request->has('fotos_eliminar')) {
            foreach ($request->fotos_eliminar as $fotoId) {
                $foto = MascotaFoto::where('id', $fotoId)
                    ->where('mascota_id', $mascota->id)
                    ->first();
                
                if ($foto) {
                    // Eliminar archivo físico
                    Storage::disk('public')->delete($foto->ruta_foto);
                    $foto->delete();
                }
            }
        }

        // Agregar nuevas fotos
        if ($request->hasFile('nuevas_fotos')) {
            foreach ($request->file('nuevas_fotos') as $foto) {
                if ($foto->isValid()) {
                    $path = $foto->store('mascotas/' . $mascota->id, 'public');

                    MascotaFoto::create([
                        'mascota_id' => $mascota->id,
                        'ruta_foto' => $path,
                        'es_principal' => false
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Mascota actualizada correctamente',
            'mascota' => $mascota->fresh(['caracteristicas', 'fotos'])
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        $mascotas = Mascota::with([
            'caracteristicas', 
            'fotos', 
            'baja', 
            'usuario'
        ])
        ->where('usuario_id', $usuario->id)
        ->whereNull('deleted_at')
        ->get()
        ->map(function ($mascota) {
            // Procesar fotos para asegurar URLs correctas
            $fotosProcesadas = $mascota->fotos->map(function ($foto) {
                // Asegurar que los accessors se carguen
                return [
                    'id' => $foto->id,
                    'mascota_id' => $foto->mascota_id,
                    'ruta_foto' => $foto->ruta_foto,
                    'es_principal' => $foto->es_principal,
                    'url' => $foto->url, // Esto llama al accessor
                    'is_external' => $foto->is_external,
                    'optimized_urls' => $foto->optimized_urls // Esto llama al accessor con caché
                ];
            });
            
            return [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'sexo' => $mascota->sexo,
                'edad_formateada' => $mascota->edadRelacion ? $mascota->edad_formateada : 'Edad no disponible',
                'foto_principal_url' => $mascota->foto_principal_url,
                'caracteristicas' => $mascota->caracteristicas,
                'fotos' => $fotosProcesadas, // Usar fotos procesadas
                'cantidadFotos' => $mascota->fotos->count(),
                'usuario' => $mascota->usuario ? [
                    'id' => $mascota->usuario->id,
                    'nombre' => $mascota->usuario->nombre,
                    'email' => $mascota->usuario->email
                ] : null
            ];
        });

        Log::info('Mascotas del usuario:', [
            'usuario_id' => $usuario->id,
            'total' => $mascotas->count(),
            'mascotas' => $mascotas->pluck('id')->toArray()
        ]);

        return response()->json([
            'success' => true,
            'mascotas' => $mascotas,
            'cantidadMascotas' => $mascotas->count()
        ]);
    }

    public function darDeBaja(Request $request, $id)
    {
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de mascota inválido'
            ], 422);
        }

        // ✅ CAMBIO AQUÍ: Eliminar la validación 'exists:motivos_baja,id'
        $request->validate([
            'motivo_baja_id' => 'required|integer|min:1|max:8', // Solo validar que sea 1-8
            'observacion' => 'nullable|string|max:500'
        ]);

        $usuario = Auth::user()->userable;

        try {
            // Buscar la mascota del usuario
            $mascota = Mascota::where('id', $id)
                ->where('usuario_id', $usuario->id)
                ->first();

            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Verificar que la mascota no esté ya dada de baja
            if ($mascota->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La mascota ya está dada de baja'
                ], 409);
            }

            // Usar el método del modelo para dar de baja
            // NOTA: Si tu modelo Mascota::darDeBaja() espera un ID de motivo,
            // puedes guardarlo como número nada más
            $resultado = $mascota->darDeBaja(
                $request->motivo_baja_id,
                $request->observacion,
                $usuario->id
            );

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mascota dada de baja correctamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja la mascota'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error al dar de baja mascota ID ' . $id . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerMotivosBaja()
    {
        try {
            $motivos = MotivoBaja::where('activo', true)
                ->get(['id', 'descripcion']);

            return response()->json([
                'success' => true,
                'motivos' => $motivos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener motivos de baja: ' . $e->getMessage());
            
            // Fallback con motivos por defecto
            $motivosPorDefecto = [
                ['id' => 1, 'descripcion' => 'Fallecimiento de la mascota'],
                ['id' => 2, 'descripcion' => 'Extraviada'],
                ['id' => 3, 'descripcion' => 'Adoptada por otra persona'],
                ['id' => 4, 'descripcion' => 'Traslado de domicilio'],
                ['id' => 5, 'descripcion' => 'Problemas de convivencia'],
            ];

            return response()->json([
                'success' => true,
                'motivos' => $motivosPorDefecto
            ]);
        }
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'termino' => 'required|string|min:1|max:255',
            'tipo' => 'nullable|in:nombre,tutor,especie'
        ]);

        $termino = $request->termino;
        $tipo = $request->tipo ?? 'nombre';

        Log::info('=== INICIO BÚSQUEDA MASCOTAS ===');
        Log::info('Término:', ['termino' => $termino, 'tipo' => $tipo]);

        $query = Mascota::with([
            'usuario.user',
            'usuario.contacto',
            'fotos',  // Cargar las fotos
            'caracteristicas'
        ])->whereNull('deleted_at');

        switch ($tipo) {
            case 'nombre':
                $query->where('nombre', 'LIKE', "%{$termino}%");
                break;
            case 'tutor':
                $query->whereHas('usuario.user', function($q) use ($termino) {
                    $q->where('name', 'LIKE', "%{$termino}%")
                    ->orWhere('email', 'LIKE', "%{$termino}%");
                });
                break;
            case 'especie':
                $query->where('especie', 'LIKE', "%{$termino}%");
                break;
        }

        $mascotas = $query->limit(50)->get();
        
        Log::info('Resultados encontrados:', [
            'total' => $mascotas->count(),
            'mascotas_ids' => $mascotas->pluck('id')->toArray()
        ]);

        // ✅ TRANSFORMACIÓN CORREGIDA - Mantener los objetos con sus accessors
        $mascotasTransformadas = $mascotas->map(function($mascota) {
            // Mantener como objetos Eloquent (no convertir a array todavía)
            $mascotaData = [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'sexo' => $mascota->sexo,
                'castrado' => $mascota->castrado,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'usuario_id' => $mascota->usuario_id,
                'created_at' => $mascota->created_at,
                'updated_at' => $mascota->updated_at,
                'deleted_at' => $mascota->deleted_at,
                'fotos' => $mascota->fotos->map(function($foto) {
                    // ✅ Incluir TODOS los atributos y accessors
                    return [
                        'id' => $foto->id,
                        'mascota_id' => $foto->mascota_id,
                        'ruta_foto' => $foto->ruta_foto,
                        'es_principal' => $foto->es_principal,
                        'created_at' => $foto->created_at,
                        'updated_at' => $foto->updated_at,
                        'url' => $foto->url,  // ✅ Accessor
                        'is_external' => $foto->is_external,  // ✅ Accessor
                        'optimized_urls' => $foto->optimized_urls  // ✅ Accessor
                    ];
                }),
                'caracteristicas' => $mascota->caracteristicas,
                'foto_principal_url' => $mascota->foto_principal_url,  // ✅ Accessor del modelo Mascota
                'usuario' => $mascota->usuario ? [
                    'id' => $mascota->usuario->id,
                    'nombre' => $mascota->usuario->nombre,
                    'user_id' => $mascota->usuario->user_id,
                    'email' => $mascota->usuario->user ? $mascota->usuario->user->email : null,
                    'contacto' => $mascota->usuario->contacto ? [
                        'email' => $mascota->usuario->contacto->email,
                        'telefono' => $mascota->usuario->contacto->telefono
                    ] : null
                ] : null
            ];
            
            return $mascotaData;
        });

        return response()->json([
            'success' => true,
            'mascotas' => $mascotasTransformadas,
            'total' => $mascotas->count()
        ]);
    }

    public function misMascotas(Request $request)
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        $mascotas = Mascota::with([
            'caracteristicas', 
            'fotos', 
        ])
        ->where('usuario_id', $usuario->id)
        ->whereNull('deleted_at')
        ->get()
        ->map(function ($mascota) {
            return [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'sexo' => $mascota->sexo,
                'edad_formateada' => $mascota->edad_formateada,
                'edad' => $mascota->edadRelacion ? $mascota->edadRelacion->años : null,
                'descripcion' => $mascota->caracteristicas->descripcion ?? '',
                'raza' => $mascota->caracteristicas->raza ?? 'No especificada',
                'foto' => $mascota->foto_principal_url,
                'foto_url' => $mascota->foto_principal_url,
                'fotos' => $mascota->fotos,
                'caracteristicas' => $mascota->caracteristicas
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mascotas,
            'count' => $mascotas->count()
        ]);
    }

    /**
     * Obtener mascotas disponibles para adopción (no están en adopción activa)
     */
    public function misMascotasDisponibles(Request $request)
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        // 1. Obtener IDs de mascotas que YA tienen ofertas de adopción activas
        $mascotasEnAdopcionIds = \App\Models\OfertaAdopcion::where('id_usuario_responsable', $user->id)
            ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
            ->pluck('id_mascota')
            ->toArray();
        
        Log::info('Mascotas en adopción activa:', ['ids' => $mascotasEnAdopcionIds]);
        
        // 2. Obtener todas las mascotas del usuario que NO están en adopción activa
        $mascotasDisponibles = Mascota::with([
            'caracteristicas', 
            'fotos', 
        ])
        ->where('usuario_id', $usuario->id)
        ->whereNull('deleted_at')
        ->whereNotIn('id', $mascotasEnAdopcionIds)
        ->get()
        ->map(function ($mascota) {
            return [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'sexo' => $mascota->sexo,
                'edad_formateada' => $mascota->edad_formateada,
                'edad' => $mascota->edadRelacion ? $mascota->edadRelacion->años : null,
                'descripcion' => $mascota->caracteristicas->descripcion ?? '',
                'raza' => $mascota->caracteristicas->raza ?? 'No especificada',
                'foto' => $mascota->foto_principal_url,
                'foto_url' => $mascota->foto_principal_url,
                'caracteristicas' => $mascota->caracteristicas
            ];
        });

        Log::info('Mascotas disponibles encontradas:', [
            'count' => $mascotasDisponibles->count(),
            'ids' => $mascotasDisponibles->pluck('id')->toArray()
        ]);

        return response()->json([
            'success' => true,
            'data' => $mascotasDisponibles->values(),
            'count' => $mascotasDisponibles->count()
        ]);
    }

    /**
     * Obtener mascotas que están en adopción
     */
    public function misMascotasEnAdopcion(Request $request)
    {
        $user = Auth::user();
        $usuario = $user->userable;
        
        // Obtener IDs de mascotas en adopción (por ahora vacío)
        $mascotasEnAdopcionIds = [];
        
        $mascotasEnAdopcion = Mascota::with([
            'caracteristicas', 
            'fotos', 
        ])
        ->where('usuario_id', $usuario->id)
        ->whereNull('deleted_at')
        ->whereIn('id', $mascotasEnAdopcionIds)
        ->get()
        ->map(function ($mascota) {
            return [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'sexo' => $mascota->sexo,
                'edad_formateada' => $mascota->edad_formateada,
                'edad' => $mascota->edadRelacion ? $mascota->edadRelacion->años : null,
                'descripcion' => $mascota->caracteristicas->descripcion ?? '',
                'raza' => $mascota->caracteristicas->raza ?? 'No especificada',
                'foto' => $mascota->foto_principal_url,
                'foto_url' => $mascota->foto_principal_url,
                'caracteristicas' => $mascota->caracteristicas
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mascotasEnAdopcion,
            'count' => $mascotasEnAdopcion->count()
        ]);
    }

    // En MascotaController.php, agregar este método:

    /**
     * Verificar si el usuario actual puede ver el historial médico de una mascota
     */
    public function verificarPermisosHistorial($mascotaId)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->userable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                    'es_tutor' => false,
                    'tiene_permiso' => false
                ], 401);
            }
            
            $usuarioActualId = $user->userable->id;
            
            // Obtener la mascota con su información de tutor
            $mascota = Mascota::with(['usuario'])->find($mascotaId);
            
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada',
                    'es_tutor' => false,
                    'tiene_permiso' => false
                ], 404);
            }
            
            // ✅ VERIFICACIÓN CRÍTICA: ¿Es el usuario actual el tutor de la mascota?
            $esTutor = ($mascota->usuario_id == $usuarioActualId);
            
            // Si es el tutor, SIEMPRE tiene permiso
            if ($esTutor) {
                Log::info('Usuario es tutor - Acceso total al historial', [
                    'mascota_id' => $mascotaId,
                    'usuario_id' => $usuarioActualId
                ]);
                
                return response()->json([
                    'success' => true,
                    'es_tutor' => true,
                    'tiene_permiso' => true,
                    'mensaje' => 'Tienes acceso completo como tutor'
                ]);
            }
            
            // Si NO es tutor, verificar si hay oferta de adopción activa con permiso
            $oferta = \App\Models\OfertaAdopcion::where('id_mascota', $mascotaId)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->first();
            
            $tienePermiso = false;
            $mensaje = '';
            
            if ($oferta) {
                $tienePermiso = $oferta->permiso_historial_medico ?? false;
                $mensaje = $tienePermiso 
                    ? 'El tutor ha compartido el historial médico'
                    : 'El tutor no ha autorizado compartir el historial médico';
                    
                Log::info('Verificación de permiso para no-tutor', [
                    'mascota_id' => $mascotaId,
                    'usuario_id' => $usuarioActualId,
                    'oferta_id' => $oferta->id_oferta,
                    'permiso_historial' => $tienePermiso
                ]);
            } else {
                $mensaje = 'No hay oferta de adopción activa para esta mascota';
                Log::info('No hay oferta activa para mascota', [
                    'mascota_id' => $mascotaId,
                    'usuario_id' => $usuarioActualId
                ]);
            }
            
            return response()->json([
                'success' => true,
                'es_tutor' => false,
                'tiene_permiso' => $tienePermiso,
                'mensaje' => $mensaje,
                'oferta_id' => $oferta->id_oferta ?? null,
                'permiso_contacto' => $oferta->permiso_contacto_tutor ?? false,
                'medios_contacto' => $oferta->medios_contacto_seleccionados ?? []
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al verificar permisos de historial', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar permisos',
                'es_tutor' => false,
                'tiene_permiso' => false
            ], 500);
        }
    }

    /**
     * Obtener el historial médico completo de una mascota
     * (con verificación de permisos)
     */
    public function getHistorialMedico($mascotaId)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->userable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            $usuarioActualId = $user->userable->id;
            
            // Obtener la mascota
            $mascota = Mascota::with(['usuario'])->find($mascotaId);
            
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }
            
            // ✅ Verificar permisos
            $esTutor = ($mascota->usuario_id == $usuarioActualId);
            $tienePermiso = $esTutor;
            
            if (!$esTutor) {
                // Verificar oferta de adopción
                $oferta = \App\Models\OfertaAdopcion::where('id_mascota', $mascotaId)
                    ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                    ->first();
                    
                $tienePermiso = $oferta && $oferta->permiso_historial_medico;
                
                if (!$tienePermiso) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para ver este historial médico',
                        'es_tutor' => false,
                        'tiene_permiso' => false
                    ], 403);
                }
            }
            
            // Obtener historial preventivo
            $historialPreventivo = [
                'vacunas' => \App\Models\ProcedimientosMedicos\Vacuna::where('mascota_id', $mascotaId)
                    ->orderBy('fecha_aplicacion', 'desc')
                    ->get(),
                'desparasitaciones' => \App\Models\ProcedimientosMedicos\Desparasitacion::where('mascota_id', $mascotaId)
                    ->orderBy('fecha', 'desc')
                    ->get(),
                'revisiones' => \App\Models\ProcedimientosMedicos\Revision::where('mascota_id', $mascotaId)
                    ->orderBy('fecha', 'desc')
                    ->get(),
                'alergias' => \App\Models\ProcedimientosMedicos\Alergia::where('mascota_id', $mascotaId)
                    ->orderBy('created_at', 'desc')
                    ->get(),
            ];
            
            // Obtener historial clínico
            $historialClinico = [
                'cirugias' => \App\Models\ProcedimientosMedicos\Cirugia::where('mascota_id', $mascotaId)
                    ->orderBy('fecha', 'desc')
                    ->get(),
                'farmacos' => \App\Models\ProcedimientosMedicos\Farmaco::where('mascota_id', $mascotaId)
                    ->orderBy('fecha_inicio', 'desc')
                    ->get(),
                'terapias' => \App\Models\ProcedimientosMedicos\Terapia::where('mascota_id', $mascotaId)
                    ->orderBy('fecha', 'desc')
                    ->get(),
                'diagnosticos' => \App\Models\ProcedimientosMedicos\Diagnostico::where('mascota_id', $mascotaId)
                    ->orderBy('fecha', 'desc')
                    ->get(),
                'paliativos' => \App\Models\ProcedimientosMedicos\CuidadoPaliativo::where('mascota_id', $mascotaId)
                    ->orderBy('fecha_inicio', 'desc')
                    ->get(),
            ];
            
            Log::info('Historial médico obtenido', [
                'mascota_id' => $mascotaId,
                'es_tutor' => $esTutor,
                'tiene_permiso' => $tienePermiso,
                'preventivo_count' => array_sum(array_map('count', $historialPreventivo)),
                'clinico_count' => array_sum(array_map('count', $historialClinico))
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'preventivo' => $historialPreventivo,
                    'clinico' => $historialClinico
                ],
                'es_tutor' => $esTutor,
                'tiene_permiso' => $tienePermiso,
                'mascota' => [
                    'id' => $mascota->id,
                    'nombre' => $mascota->nombre,
                    'especie' => $mascota->especie
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener historial médico', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial médico'
            ], 500);
        }
    }
}
