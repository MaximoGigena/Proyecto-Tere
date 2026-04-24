<?php

namespace App\Http\Controllers;

use App\Models\Veterinario;
use App\Models\CaracteristicasVeterinario;
use App\Models\ContactoVeterinario;
use App\Models\FotoVeterinario;
use App\Models\SolicitudVeterinario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class VeterinarioController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:solicitudes_veterinarios,email',
            'matricula' => 'required|string|unique:veterinarios,matricula|unique:solicitudes_veterinarios,matricula',
            'especialidad' => 'required|string|max:150',
            'experiencia' => 'nullable|integer|min:0',
            'descripcion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'emailContacto' => 'nullable|email',
            'foto0' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto4' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto5' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

       if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Guardar las fotos
            $fotosGuardadas = $this->guardarFotos($request);

            // Crear la solicitud en lugar del veterinario directo
            $solicitud = SolicitudVeterinario::create([
                'nombre_completo' => $request->nombre,
                'email' => $request->email,
                'matricula' => $request->matricula,
                'especialidad' => $request->especialidad,
                'anos_experiencia' => $request->experiencia ?? 0,
                'descripcion' => $request->descripcion,
                'telefono' => $request->telefono,
                'email_contacto' => $request->emailContacto,
                'fotos' => $fotosGuardadas,
                'estado' => SolicitudVeterinario::ESTADO_PENDIENTE,
                'fecha_solicitud' => now()
            ]);

            // Crear el veterinario temporal con estado pendiente
            $veterinario = Veterinario::create([
                'nombre_completo' => $request->nombre,
                'matricula' => $request->matricula,
                'especialidad' => $request->especialidad,
                'foto' => $fotosGuardadas[0] ?? null,
                'estado' => Veterinario::ESTADO_PENDIENTE,
                'activo' => false // Inactivo hasta ser aprobado
            ]);


            // Crear el usuario asociado al veterinario (temporal)
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make(uniqid()), // Password temporal
                'userable_type' => 'App\Models\Veterinario',
                'userable_id' => $veterinario->id,
                'estado' => 'pendiente'
            ]);

            // Crear características temporales
            if ($request->experiencia || $request->descripcion) {
                CaracteristicasVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'anos_experiencia' => $request->experiencia ?? 0,
                    'descripcion' => $request->descripcion
                ]);
            }

            // Crear contactos temporales
            if ($request->telefono) {
                ContactoVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'tipo' => 'telefono',
                    'valor' => $request->telefono
                ]);
            }

            if ($request->emailContacto) {
                ContactoVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'tipo' => 'email',
                    'valor' => $request->emailContacto
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud enviada exitosamente. Espera la aprobación del administrador.',
                'data' => [
                    'solicitud_id' => $solicitud->id,
                    'veterinario_id' => $veterinario->id,
                    'redirect_to' => '/veterinario-pendiente'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un veterinario específico por ID (para modificación)
     */
    public function show($id)
    {
        try {
            // Buscar el veterinario con sus relaciones
            $veterinario = Veterinario::with(['caracteristicas', 'mediosContacto', 'fotos'])
                ->findOrFail($id);
            
            // Obtener el usuario asociado
            $user = User::where('userable_type', 'App\Models\Veterinario')
                ->where('userable_id', $veterinario->id)
                ->first();
            
            // Verificar permisos: solo el propio veterinario o admin pueden ver
            $authUser = auth()->user();
            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            // Si es veterinario, solo puede ver su propio perfil
            if ($authUser->userable_type === 'App\Models\Veterinario' && $authUser->userable_id != $veterinario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este perfil'
                ], 403);
            }
            
            // Estructurar los datos para el frontend
            $data = [
                'id' => $veterinario->id,
                'nombre' => $veterinario->nombre_completo,
                'email' => $user ? $user->email : null,
                'matricula' => $veterinario->matricula,
                'especialidad' => $veterinario->especialidad,
                'experiencia' => $veterinario->caracteristicas ? $veterinario->caracteristicas->anos_experiencia : 0,
                'descripcion' => $veterinario->caracteristicas ? $veterinario->caracteristicas->descripcion : '',
                'telefono' => $this->obtenerContactoPorTipo($veterinario, 'telefono'),
                'email_contacto' => $this->obtenerContactoPorTipo($veterinario, 'email'),
                'estado' => $veterinario->estado,
                'fotos' => $veterinario->fotos->map(function($foto) {
                    return $foto->url; // Devuelve la URL completa de cada foto
                })->toArray()
            ];
            
            // Si no hay fotos en la tabla fotos, usar la foto principal del campo 'foto'
            if (empty($data['fotos']) && $veterinario->foto) {
                $data['fotos'] = [Storage::url($veterinario->foto)];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener veterinario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los datos del veterinario',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Método para que el administrador apruebe una solicitud
     */
    public function aprobarSolicitud($solicitudId)
    {
        try {
            DB::beginTransaction();

            $solicitud = SolicitudVeterinario::findOrFail($solicitudId);

            // Buscar el veterinario temporal asociado a esta solicitud
            $veterinario = Veterinario::where('matricula', $solicitud->matricula)
                                    ->where('estado', Veterinario::ESTADO_PENDIENTE)
                                    ->firstOrFail();

            // NUEVO: Guardar TODAS las fotos en la tabla independiente
            $fotosGuardadas = $solicitud->guardarFotosEnVeterinario($veterinario->id);
            
            // Obtener la ruta de la foto principal para el campo 'foto' (por compatibilidad)
            $rutaFotoPrincipal = null;
            if (!empty($fotosGuardadas)) {
                $fotoPrincipal = $fotosGuardadas[0];
                $rutaFotoPrincipal = $fotoPrincipal->ruta;
            }

            // Actualizar el veterinario a estado aprobado
            $veterinario->update([
                'estado' => Veterinario::ESTADO_APROBADO,
                'activo' => true,
                'foto' => $rutaFotoPrincipal // Mantenemos este campo por compatibilidad
            ]);

            // Actualizar el usuario a estado activo
            $user = User::where('email', $solicitud->email)->first();
            if ($user) {
                $user->update([
                    'estado' => 'activo'
                ]);
            }

            // Actualizar características si hay diferencias
            $caracteristicas = $veterinario->caracteristicas;
            if ($caracteristicas) {
                $caracteristicas->update([
                    'anos_experiencia' => $solicitud->anos_experiencia,
                    'descripcion' => $solicitud->descripcion
                ]);
            }

            // Actualizar contactos desde la solicitud
            $this->actualizarContactosDesdeSolicitud($veterinario, $solicitud);

            // Actualizar estado de la solicitud
            $solicitud->update(['estado' => SolicitudVeterinario::ESTADO_APROBADO]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada exitosamente',
                'veterinario_id' => $veterinario->id,
                'fotos_guardadas' => count($fotosGuardadas)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log del error para depuración
            Log::error('Error al aprobar solicitud: ' . $e->getMessage(), [
                'exception' => $e,
                'solicitud_id' => $solicitudId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar los datos de un veterinario
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Buscar el veterinario
            $veterinario = Veterinario::with(['caracteristicas', 'mediosContacto', 'fotos'])
                ->findOrFail($id);

            // Verificar permisos
            $authUser = auth()->user();
            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Solo el propio veterinario o un administrador pueden modificar
            $esPropietario = ($authUser->userable_type === 'App\Models\Veterinario' && $authUser->userable_id == $veterinario->id);
            $esAdmin = ($authUser->userable_type === 'App\Models\Administrador');

            if (!$esPropietario && !$esAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para modificar este perfil'
                ], 403);
            }

            // Validar los datos
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'matricula' => 'required|string|unique:veterinarios,matricula,' . $id,
                'especialidad' => 'required|string|max:150',
                'experiencia' => 'nullable|integer|min:0',
                'descripcion' => 'nullable|string|max:500',
                'telefono' => 'nullable|string|max:20',
                'emailContacto' => 'nullable|email',
                'foto0' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'foto1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'foto2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'foto3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'foto4' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'foto5' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar datos básicos del veterinario
            $veterinario->update([
                'nombre_completo' => $request->nombre,
                'matricula' => $request->matricula,
                'especialidad' => $request->especialidad,
            ]);

            // Actualizar o crear características
            if ($veterinario->caracteristicas) {
                $veterinario->caracteristicas->update([
                    'anos_experiencia' => $request->experiencia ?? 0,
                    'descripcion' => $request->descripcion
                ]);
            } else {
                CaracteristicasVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'anos_experiencia' => $request->experiencia ?? 0,
                    'descripcion' => $request->descripcion
                ]);
            }

            // Actualizar contactos
            // Eliminar contactos existentes
            $veterinario->mediosContacto()->delete();

            // Crear nuevo teléfono si se proporcionó
            if ($request->telefono) {
                ContactoVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'tipo' => 'telefono',
                    'valor' => $request->telefono
                ]);
            }

            // Crear nuevo email de contacto si se proporcionó
            if ($request->emailContacto) {
                ContactoVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'tipo' => 'email',
                    'valor' => $request->emailContacto
                ]);
            }

            // Procesar nuevas fotos si se enviaron
            $nuevasFotos = [];
            for ($i = 0; $i < 6; $i++) {
                $fieldName = "foto{$i}";
                if ($request->hasFile($fieldName)) {
                    $foto = $request->file($fieldName);
                    $path = $foto->store('veterinarios/' . $veterinario->id, 'public');
                    $nuevasFotos[] = $path;
                }
            }

            // Si se subieron nuevas fotos, actualizar la galería
            if (!empty($nuevasFotos)) {
                // Eliminar fotos antiguas (opcional: solo si se subieron nuevas)
                foreach ($veterinario->fotos as $fotoAntigua) {
                    Storage::disk('public')->delete($fotoAntigua->ruta);
                    $fotoAntigua->delete();
                }

                // Guardar nuevas fotos
                foreach ($nuevasFotos as $index => $path) {
                    FotoVeterinario::create([
                        'veterinario_id' => $veterinario->id,
                        'ruta' => $path,
                        'orden' => $index,
                        'tipo' => $index === 0 ? 'perfil' : 'galeria',
                        'activa' => true
                    ]);
                }

                // Actualizar la foto principal en el campo 'foto' (por compatibilidad)
                $veterinario->update([
                    'foto' => $nuevasFotos[0]
                ]);
            }

            DB::commit();

            // Obtener los datos actualizados para la respuesta
            $veterinarioActualizado = Veterinario::with(['caracteristicas', 'mediosContacto', 'fotos'])
                ->find($veterinario->id);
            
            $user = User::where('userable_type', 'App\Models\Veterinario')
                ->where('userable_id', $veterinario->id)
                ->first();

            $responseData = [
                'id' => $veterinarioActualizado->id,
                'nombre' => $veterinarioActualizado->nombre_completo,
                'email' => $user ? $user->email : null,
                'matricula' => $veterinarioActualizado->matricula,
                'especialidad' => $veterinarioActualizado->especialidad,
                'experiencia' => $veterinarioActualizado->caracteristicas ? $veterinarioActualizado->caracteristicas->anos_experiencia : 0,
                'descripcion' => $veterinarioActualizado->caracteristicas ? $veterinarioActualizado->caracteristicas->descripcion : '',
                'telefono' => $this->obtenerContactoPorTipo($veterinarioActualizado, 'telefono'),
                'email_contacto' => $this->obtenerContactoPorTipo($veterinarioActualizado, 'email'),
                'estado' => $veterinarioActualizado->estado,
                'fotos' => $veterinarioActualizado->fotos->map(function($foto) {
                    return $foto->url;
                })->toArray()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Veterinario actualizado exitosamente',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar veterinario: ' . $e->getMessage(), [
                'veterinario_id' => $id,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los datos del veterinario',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Método auxiliar para actualizar contactos desde la solicitud
     */
    private function actualizarContactosDesdeSolicitud($veterinario, $solicitud)
    {
        // Eliminar contactos existentes
        $veterinario->mediosContacto()->delete();

        // Crear contacto de teléfono si existe en la solicitud
        if ($solicitud->telefono) {
            ContactoVeterinario::create([
                'veterinario_id' => $veterinario->id,
                'tipo' => 'telefono',
                'valor' => $solicitud->telefono
            ]);
        }

        // Crear contacto de email si existe en la solicitud
        if ($solicitud->email_contacto) {
            ContactoVeterinario::create([
                'veterinario_id' => $veterinario->id,
                'tipo' => 'email',
                'valor' => $solicitud->email_contacto
            ]);
        }
    }

    /**
     * Método para rechazar una solicitud
     */
    public function rechazarSolicitud($solicitudId)
    {
        try {
            DB::beginTransaction();

            $solicitud = SolicitudVeterinario::findOrFail($solicitudId);

            // Buscar el veterinario temporal asociado
            $veterinario = Veterinario::where('matricula', $solicitud->matricula)
                                    ->where('estado', Veterinario::ESTADO_PENDIENTE)
                                    ->first();

            if ($veterinario) {
                // Actualizar estado del veterinario a rechazado
                $veterinario->update([
                    'estado' => Veterinario::ESTADO_RECHAZADO,
                    'activo' => false
                ]);

                // Opcional: desactivar el usuario
                $user = User::where('email', $solicitud->email)->first();
                if ($user) {
                    $user->update(['estado' => 'inactivo']);
                }
            }

            // Actualizar estado de la solicitud
            $solicitud->update(['estado' => SolicitudVeterinario::ESTADO_RECHAZADO]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda las fotos y devuelve un array con las rutas
     */
    private function guardarFotos(Request $request)
    {
        $fotosGuardadas = [];
        
        for ($i = 0; $i < 6; $i++) {
            $fieldName = "foto{$i}";
            
            if ($request->hasFile($fieldName)) {
                $foto = $request->file($fieldName);
                $path = $foto->store('solicitudes-veterinarios', 'public');
                $fotosGuardadas[] = $path;
            }
        }
        
        return $fotosGuardadas;
    }

    /**
     * Obtener todas las solicitudes pendientes (para el admin)
     */
    public function obtenerSolicitudesPendientes()
    {
        $solicitudes = SolicitudVeterinario::where('estado', SolicitudVeterinario::ESTADO_PENDIENTE)
            ->orderBy('fecha_solicitud', 'desc')
            ->get()
            ->map(function ($solicitud) {
                // Incluir las URLs de las fotos usando el accesor
                $solicitudData = $solicitud->toArray();
                $solicitudData['fotos_urls'] = $solicitud->fotos_urls;
                return $solicitudData;
            });

        return response()->json([
            'success' => true,
            'data' => $solicitudes
        ]);
    }

    /**
     * Obtener veterinarios por estado (para el admin)
     */
    public function obtenerVeterinariosPorEstado($estado)
    {
        $veterinarios = Veterinario::where('estado', $estado)
            ->with(['user', 'caracteristicas', 'mediosContacto'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $veterinarios
        ]);
    }

    /**
     * Obtener el perfil del veterinario autenticado
     */
    public function obtenerPerfil()
    {
        try {
            $user = auth()->user();
            
            if (!$user || $user->userable_type !== 'App\Models\Veterinario') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autorizado'
                ], 403);
            }
            
            $veterinario = Veterinario::with(['caracteristicas', 'mediosContacto', 'fotos'])
                ->where('id', $user->userable_id)
                ->first();
            
            if (!$veterinario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil de veterinario no encontrado'
                ], 404);
            }
            
            $perfilData = [
                'id' => $veterinario->id, // ✅ AGREGAR ESTA LÍNEA - El ID del veterinario
                'foto' => $veterinario->foto,
                'nombre' => $veterinario->nombre_completo,
                'matricula' => $veterinario->matricula,
                'especialidad' => $veterinario->especialidad,
                'email' => $user->email,
                'telefono' => $this->obtenerContactoPorTipo($veterinario, 'telefono'),
                'email_contacto' => $this->obtenerContactoPorTipo($veterinario, 'email'),
                'experiencia' => $veterinario->caracteristicas ? $veterinario->caracteristicas->anos_experiencia : 0,
                'descripcion' => $veterinario->caracteristicas ? $veterinario->caracteristicas->descripcion : '',
                'estado' => $veterinario->estado,
                'activo' => $veterinario->activo,
                'fotos' => $veterinario->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'url' => $foto->url,
                        'orden' => $foto->orden,
                        'tipo' => $foto->tipo,
                        'activa' => $foto->activa
                    ];
                }),
                'foto_principal' => $veterinario->fotoPrincipal ? $veterinario->fotoPrincipal->url : null
            ];
            
            return response()->json([
                'success' => true,
                'data' => $perfilData // Ahora contiene 'id'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener perfil: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el perfil',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Cerrar sesión del veterinario autenticado
     */
    public function logout(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado
            $user = $request->user(); // O auth()->user()
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay sesión activa'
                ], 401);
            }
            
            // IMPORTANTE: Revocar el token actual (Sanctum)
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            
            // Opcional: Revocar TODOS los tokens del usuario
            // $user->tokens()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
                'redirect_to' => '/'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Método auxiliar para obtener contacto por tipo
     */
    private function obtenerContactoPorTipo($veterinario, $tipo)
    {
        $contacto = $veterinario->mediosContacto->firstWhere('tipo', $tipo);
        return $contacto ? $contacto->valor : null;
    }

    /**
     * Método auxiliar para actualizar fotos de un veterinario
     */
    public function actualizarFotosVeterinario(Request $request, $veterinarioId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fotos' => 'array',
                'fotos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'orden' => 'array',
                'orden.*' => 'integer'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $veterinario = Veterinario::findOrFail($veterinarioId);
            
            DB::beginTransaction();
            
            // Opción 1: Eliminar todas las fotos existentes y subir nuevas
            if ($request->hasFile('fotos')) {
                // Eliminar fotos viejas
                foreach ($veterinario->fotos as $foto) {
                    Storage::disk('public')->delete($foto->ruta);
                    $foto->delete();
                }
                
                // Subir nuevas fotos
                $orden = $request->input('orden', []);
                foreach ($request->file('fotos') as $index => $file) {
                    $path = $file->store('veterinarios/' . $veterinarioId, 'public');
                    
                    FotoVeterinario::create([
                        'veterinario_id' => $veterinario->id,
                        'ruta' => $path,
                        'orden' => $orden[$index] ?? $index,
                        'tipo' => ($orden[$index] ?? $index) === 0 ? 'perfil' : 'galeria',
                        'activa' => true
                    ]);
                }
                
                // Actualizar foto principal en veterinario (por compatibilidad)
                $fotoPrincipal = $veterinario->fotos()->where('orden', 0)->first();
                if ($fotoPrincipal) {
                    $veterinario->update(['foto' => $fotoPrincipal->ruta]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Fotos actualizadas correctamente',
                'fotos' => $veterinario->fotos
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar fotos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para depurar las URLs de las fotos
     */
    public function debugFotos($veterinarioId)
    {
        try {
            $veterinario = Veterinario::with('fotos')->findOrFail($veterinarioId);
            
            $debugInfo = [
                'veterinario_id' => $veterinario->id,
                'foto_campo_raw' => $veterinario->getRawOriginal('foto'),
                'foto_campo_con_accesor' => $veterinario->foto,
                'fotos_relacion' => $veterinario->fotos->map(function($foto) {
                    return [
                        'id' => $foto->id,
                        'ruta_raw' => $foto->ruta,
                        'url_accesor' => $foto->url,
                        'url_manual' => Storage::url($foto->ruta),
                        'existe_fisicamente' => Storage::disk('public')->exists($foto->ruta),
                        'ruta_completa_fisica' => storage_path('app/public/' . $foto->ruta),
                        'orden' => $foto->orden,
                        'tipo' => $foto->tipo
                    ];
                }),
                'storage_config' => [
                    'disk' => config('filesystems.default'),
                    'public_url' => config('filesystems.disks.public.url'),
                    'app_url' => config('app.url')
                ]
            ];
            
            return response()->json($debugInfo);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}