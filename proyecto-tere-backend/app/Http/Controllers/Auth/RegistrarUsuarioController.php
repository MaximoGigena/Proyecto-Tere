<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\CaracteristicasUsuario;
use App\Models\User;
use App\Models\ContactoUsuario;
use App\Models\UsuarioFoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class RegistrarUsuarioController extends Controller
{
    public function register(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Validación de datos
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email|max:100',
                'password' => 'required|string|min:8',
                'edad' => 'nullable|integer|min:18',
                'foto_perfil' => 'nullable|image|max:2048',
                
                // Características
                'tipoVivienda' => 'nullable|string',
                'ocupacion' => 'nullable|string',
                'experiencia' => 'nullable|string',
                'convivenciaNiños' => 'nullable|string',
                'convivenciaMascotas' => 'nullable|string',
                'descripcion' => 'nullable|string|max:500',

                // Contacto de emergencia
                'dni' => 'nullable|string|max:20|unique:usuario_contacto,dni',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:100|unique:usuario_contacto,email',
                'nombre_completo' => 'nullable|string|max:200',
            ]);

            // Crear usuario
            $usuario = Usuario::create([
                'nombre' => $validatedData['nombre'],
                'edad' => $validatedData['edad'] ?? null,
                'activo' => true,
            ]);

            // Crear user (autenticación polimórfica)
            $user = User::create([
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'userable_id' => $usuario->id,
                'userable_type' => Usuario::class,
            ]);

            // Procesar foto de perfil
            $fotoPath = null;
            if ($request->hasFile('foto_perfil')) {
                $fotoPath = $request->file('foto_perfil')->store('perfiles', 'public');

                // Guardar foto en tabla usuario_fotos
                UsuarioFoto::create([
                    'usuario_id' => $usuario->id,
                    'ruta_foto' => $fotoPath,
                    'es_principal' => true
                ]);
            }

            // Crear características del usuario
            $caracteristicas = CaracteristicasUsuario::create([
                'tipoVivienda' => $validatedData['tipoVivienda'] ?? null,
                'ocupacion' => $validatedData['ocupacion'] ?? null,
                'experiencia' => $validatedData['experiencia'] ?? null,
                'convivenciaNiños' => $validatedData['convivenciaNiños'] ?? null,
                'convivenciaMascotas' => $validatedData['convivenciaMascotas'] ?? null,
                'descripción' => $validatedData['descripcion'] ?? null,
                'usuario_id' => $usuario->id,
            ]);

            // Crear datos de contacto (si se proporcionaron)
            if (!empty($validatedData['dni']) || !empty($validatedData['telefono_contacto']) || 
                !empty($validatedData['email_contacto']) || !empty($validatedData['nombre_completo'])) {
                
                ContactoUsuario::create([
                    'usuario_id' => $usuario->id,
                    'dni' => $validatedData['dni'] ?? null,
                    'telefono' => $validatedData['telefono_contacto'] ?? null,
                    'email' => $validatedData['email_contacto'] ?? $validatedData['email'],
                    'nombre_completo' => $validatedData['nombre_completo'] ?? $validatedData['nombre'],
                ]);
            }

            // AUTENTICAR AL USUARIO Y CREAR TOKEN
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            Log::info('Usuario registrado y autenticado', ['user_id' => $usuario->id]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'usuario' => $usuario,
                'caracteristicas' => $caracteristicas,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Errores de validación en registrar usuario', [
                    'errors' => $e->errors(),
                    'input' => $request->all()
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   /**
     * Obtener usuario para modificación
     */
    public function show($id)
    {
        try {
            Log::info('🔍 Solicitando usuario para modificación', ['usuario_id' => $id]);

            $usuario = Usuario::with([
                'user', 
                'caracteristicas', 
                'contacto', 
                'fotos' => function($query) {
                    $query->where('es_principal', true);
                }
            ])->findOrFail($id);

            Log::info('🔍 Usuario encontrado', [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'tiene_caracteristicas' => !is_null($usuario->caracteristicas),
                'tiene_contacto' => !is_null($usuario->contacto),
                'cantidad_fotos' => $usuario->fotos->count()
            ]);

            // Estructura más clara para el frontend
            $response = [
                'success' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'edad' => $usuario->edad,
                    'email' => $usuario->user ? $usuario->user->email : null,
                    'caracteristicas' => $usuario->caracteristicas ? [
                        'ocupacion' => $usuario->caracteristicas->ocupacion,
                        'tipoVivienda' => $usuario->caracteristicas->tipoVivienda,
                        'experiencia' => $usuario->caracteristicas->experiencia,
                        'convivenciaNiños' => $usuario->caracteristicas->convivenciaNiños,
                        'convivenciaMascotas' => $usuario->caracteristicas->convivenciaMascotas,
                        'descripción' => $usuario->caracteristicas->descripción,
                    ] : null,
                    'contacto' => $usuario->contacto ? [
                        'dni' => $usuario->contacto->dni,
                        'telefono' => $usuario->contacto->telefono,
                        'email' => $usuario->contacto->email,
                        'nombre_completo' => $usuario->contacto->nombre_completo,
                    ] : null,
                    'fotos' => $usuario->fotos->map(function($foto) {
                        return [
                            'ruta_foto' => $foto->ruta_foto,
                            'es_principal' => $foto->es_principal
                        ];
                    })
                ]
            ];

            Log::info('🔍 Enviando respuesta al frontend', ['response_structure' => array_keys($response)]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            Log::info('🔧 Iniciando actualización de usuario', ['usuario_id' => $id, 'datos' => $request->all()]);

            $usuario = Usuario::findOrFail($id);
            Log::info('🔧 Usuario encontrado', ['usuario' => $usuario]);

            // Validación
            $validatedData = $request->validate([
                'edad' => 'nullable|integer|min:14',
                'tipoVivienda' => 'nullable|string',
                'ocupacion' => 'nullable|string',
                'experiencia' => 'nullable|string',
                'convivenciaNiños' => 'nullable|string',
                'convivenciaMascotas' => 'nullable|string',
                'descripcion' => 'nullable|string|max:500',
                'dni' => 'nullable|string|max:20|unique:usuario_contacto,dni,' . ($usuario->contacto ? $usuario->contacto->id : 'NULL'),
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:100|unique:usuario_contacto,email,' . ($usuario->contacto ? $usuario->contacto->id : 'NULL'),
                'nombre_completo' => 'nullable|string|max:200',
                'foto_perfil' => 'nullable|image|max:2048',
            ]);

            Log::info('🔧 Datos validados', ['validatedData' => $validatedData]);

            // Actualizar usuario
            $usuario->update([
                'edad' => $validatedData['edad'] ?? $usuario->edad,
            ]);
            Log::info('🔧 Usuario actualizado', ['nueva_edad' => $usuario->edad]);

            // Actualizar o crear características
            if ($usuario->caracteristicas) {
                $usuario->caracteristicas->update([
                    'ocupacion' => $validatedData['ocupacion'] ?? $usuario->caracteristicas->ocupacion,
                    'tipoVivienda' => $validatedData['tipoVivienda'] ?? $usuario->caracteristicas->tipoVivienda,
                    'experiencia' => $validatedData['experiencia'] ?? $usuario->caracteristicas->experiencia,
                    'convivenciaNiños' => $validatedData['convivenciaNiños'] ?? $usuario->caracteristicas->convivenciaNiños,
                    'convivenciaMascotas' => $validatedData['convivenciaMascotas'] ?? $usuario->caracteristicas->convivenciaMascotas,
                    'descripción' => $validatedData['descripcion'] ?? $usuario->caracteristicas->descripción,
                ]);
                Log::info('🔧 Características actualizadas');
            } else {
                CaracteristicasUsuario::create([
                    'usuario_id' => $usuario->id,
                    'ocupacion' => $validatedData['ocupacion'] ?? null,
                    'tipoVivienda' => $validatedData['tipoVivienda'] ?? null,
                    'experiencia' => $validatedData['experiencia'] ?? null,
                    'convivenciaNiños' => $validatedData['convivenciaNiños'] ?? null,
                    'convivenciaMascotas' => $validatedData['convivenciaMascotas'] ?? null,
                    'descripción' => $validatedData['descripcion'] ?? null,
                ]);
                Log::info('🔧 Características creadas');
            }

            // Actualizar o crear contacto
            if ($usuario->contacto) {
                $usuario->contacto->update([
                    'dni' => $validatedData['dni'] ?? $usuario->contacto->dni,
                    'telefono' => $validatedData['telefono_contacto'] ?? $usuario->contacto->telefono,
                    'email' => $validatedData['email_contacto'] ?? $usuario->contacto->email,
                    'nombre_completo' => $validatedData['nombre_completo'] ?? $usuario->contacto->nombre_completo,
                ]);
                Log::info('🔧 Contacto actualizado');
            } else {
                ContactoUsuario::create([
                    'usuario_id' => $usuario->id,
                    'dni' => $validatedData['dni'] ?? null,
                    'telefono' => $validatedData['telefono_contacto'] ?? null,
                    'email' => $validatedData['email_contacto'] ?? null,
                    'nombre_completo' => $validatedData['nombre_completo'] ?? null,
                ]);
                Log::info('🔧 Contacto creado');
            }

            // Guardar foto de perfil si se envía
            if ($request->hasFile('foto_perfil')) {
                // Eliminar foto principal anterior si existe
                $fotoAnterior = $usuario->fotos()->where('es_principal', true)->first();
                if ($fotoAnterior) {
                    // Eliminar archivo físico
                    if (Storage::disk('public')->exists($fotoAnterior->ruta_foto)) {
                        Storage::disk('public')->delete($fotoAnterior->ruta_foto);
                    }
                    // Eliminar registro
                    $fotoAnterior->delete();
                }

                $path = $request->file('foto_perfil')->store('perfiles', 'public');
                $usuario->fotos()->create([
                    'ruta_foto' => $path,
                    'es_principal' => true
                ]);
                Log::info('🔧 Foto de perfil actualizada');
            }

            DB::commit();
            Log::info('✅ Usuario actualizado exitosamente');

            // Recargar relaciones actualizadas
            $usuario->load(['caracteristicas', 'contacto', 'fotos']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'usuario' => $usuario
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al modificar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al modificar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}