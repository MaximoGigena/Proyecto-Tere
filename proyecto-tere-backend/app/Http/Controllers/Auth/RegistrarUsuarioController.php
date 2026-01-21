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
                'edad' => 'nullable|integer|min:14',
                'foto_perfil' => 'required|image|max:2048',
                
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
            Log::info('🔍 ===== INICIANDO SOLICITUD DE USUARIO =====');
            Log::info('🔍 ID recibido en show():', ['id' => $id]);
            Log::info('🔍 Headers:', request()->headers->all());

            // Cargar todas las relaciones necesarias
            $usuario = Usuario::with([
                'user', 
                'caracteristicas', 
                'contacto', 
                'ubicaciones',
                'fotos'
            ])->findOrFail($id);  // ← Cambiado a findOrFail
            
            Log::info('✅ Usuario encontrado:', [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'tiene_user' => $usuario->user ? 'SI' : 'NO',
                'tiene_caracteristicas' => $usuario->caracteristicas ? 'SI' : 'NO'
            ]);

            // Obtener ubicación actual (más reciente)
            $ubicacionActual = $usuario->ubicaciones()->latest('location_updated_at')->first();
            
            // CALCULAR TIEMPO DE REGISTRO CON LA FECHA DEL USUARIO (NO DEL SERVER)
            $createdAt = $usuario->created_at;
            
            // Asegurar que estamos usando la fecha en UTC
            $now = now(); // Esto ya es UTC por defecto en Laravel
            
            // Usar Carbon para el cálculo correcto
            $diasRegistrado = $createdAt->diffInDays($now);
            
            // CALCULAR CORRECTAMENTE EL TIEMPO DE REGISTRO
            if ($diasRegistrado === 0) {
                $horas = $createdAt->diffInHours($now);
                if ($horas === 0) {
                    $minutos = $createdAt->diffInMinutes($now);
                    if ($minutos === 0) {
                        $tiempoRegistro = 'Hace unos segundos';
                    } else if ($minutos === 1) {
                        $tiempoRegistro = 'Hace 1 minuto';
                    } else {
                        $tiempoRegistro = "Hace {$minutos} minutos";
                    }
                } else if ($horas === 1) {
                    $tiempoRegistro = 'Hace 1 hora';
                } else {
                    $tiempoRegistro = "Hace {$horas} horas";
                }
            } else if ($diasRegistrado === 1) {
                $tiempoRegistro = 'Ayer';
            } else if ($diasRegistrado < 7) {
                $tiempoRegistro = "Hace {$diasRegistrado} días";
            } else if ($diasRegistrado < 30) {
                $semanas = floor($diasRegistrado / 7);
                $tiempoRegistro = "Hace {$semanas} semana" . ($semanas > 1 ? 's' : '');
            } else if ($diasRegistrado < 365) {
                $meses = floor($diasRegistrado / 30);
                $tiempoRegistro = "Hace {$meses} mes" . ($meses > 1 ? 'es' : '');
            } else {
                $anios = floor($diasRegistrado / 365);
                $tiempoRegistro = "Hace {$anios} año" . ($anios > 1 ? 's' : '');
            }

            Log::info('📅 Tiempo de registro calculado', [
                'created_at' => $createdAt,
                'now' => $now,
                'dias' => $diasRegistrado,
                'texto' => $tiempoRegistro
            ]);

            // OBTENER FOTO PRINCIPAL CORRECTAMENTE
            $fotoPrincipal = $usuario->fotos()->where('es_principal', true)->first();
            if (!$fotoPrincipal) {
                $fotoPrincipal = $usuario->fotos()->first();
            }

            // Estructura más clara para el frontend
            $response = [
                'success' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'edad' => $usuario->edad,
                    'ubicacion' => $ubicacionActual ? $ubicacionActual->location : null,
                    'email' => $usuario->user ? $usuario->user->email : null,
                    // DATOS DE TIEMPO DE REGISTRO
                    'tiempo_registro' => $tiempoRegistro,
                    'dias_registrado' => $diasRegistrado,
                    'created_at' => $createdAt->toISOString(),
                    // FOTO DE PERFIL
                    'foto_principal' => $fotoPrincipal ? asset('storage/' . $fotoPrincipal->ruta_foto) : null,
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
                            'url' => $foto->ruta_foto ? asset('storage/' . $foto->ruta_foto) : null,
                            'es_principal' => $foto->es_principal
                        ];
                    })
                ]
            ];

            Log::info('📤 Enviando respuesta JSON:', [
                'usuario_nombre' => $response['usuario']['nombre'],
                'tiempo_registro' => $response['usuario']['tiempo_registro'],
                'dias_registrado' => $response['usuario']['dias_registrado'],
                'foto_principal' => $response['usuario']['foto_principal'] ? 'SI' : 'NO'
            ]);

            return response()->json($response);

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ Usuario no encontrado (ModelNotFoundException):', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario'
            ], 500);
        }

    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            Log::info('🔧 ===== INICIANDO ACTUALIZACIÓN DE USUARIO =====', ['usuario_id' => $id]);
            Log::info('🔧 Datos recibidos del frontend:', $request->all());
            Log::info('🔧 Headers:', $request->headers->all());

            Log::info('🎯 ===== DEBUG COMPLETO DEL FORM DATA =====');
        
            // DEBUG: Ver el contenido RAW del request
            Log::info('🎯 CONTENIDO RAW:', ['content' => $request->getContent()]);
            
            // DEBUG: Ver todos los parámetros del FormData
            $allParams = [];
            foreach ($request->all() as $key => $value) {
                $allParams[$key] = $value;
            }
            Log::info('🎯 PARÁMETROS FORM DATA:', $allParams);
            
            // DEBUG: Ver archivos
            Log::info('🎯 ARCHIVOS:', $request->allFiles());
            
            // DEBUG: Ver método y headers
            Log::info('🎯 MÉTODO:', ['method' => $request->method()]);
            Log::info('🎯 CONTENT TYPE:', ['content_type' => $request->header('Content-Type')]);

            $usuario = Usuario::findOrFail($id);
            Log::info('🔧 Usuario encontrado en BD:', [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'edad_actual' => $usuario->edad
            ]);

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

            Log::info('🔧 Datos validados:', $validatedData);

            // DEBUG: Verificar qué campos se están enviando realmente
            Log::info('🔧 Campos recibidos en request:', array_keys($request->all()));
            
            // Verificar si hay archivos
            Log::info('🔧 ¿Tiene archivo foto_perfil?: ' . ($request->hasFile('foto_perfil') ? 'SÍ' : 'NO'));

            // Actualizar usuario
            $usuario->update([
                'edad' => $validatedData['edad'] ?? $usuario->edad,
            ]);
            Log::info('🔧 Usuario actualizado - Nueva edad:', ['nueva_edad' => $usuario->edad]);

            // Actualizar o crear características
            if ($usuario->caracteristicas) {
                $caracteristicasActualizadas = [
                    'ocupacion' => $validatedData['ocupacion'] ?? $usuario->caracteristicas->ocupacion,
                    'tipoVivienda' => $validatedData['tipoVivienda'] ?? $usuario->caracteristicas->tipoVivienda,
                    'experiencia' => $validatedData['experiencia'] ?? $usuario->caracteristicas->experiencia,
                    'convivenciaNiños' => $validatedData['convivenciaNiños'] ?? $usuario->caracteristicas->convivenciaNiños,
                    'convivenciaMascotas' => $validatedData['convivenciaMascotas'] ?? $usuario->caracteristicas->convivenciaMascotas,
                    'descripción' => $validatedData['descripcion'] ?? $usuario->caracteristicas->descripción,
                ];
                
                $usuario->caracteristicas->update($caracteristicasActualizadas);
                Log::info('🔧 Características actualizadas:', $caracteristicasActualizadas);
            } else {
                Log::info('🔧 Creando nuevas características...');
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
                $contactoActualizado = [
                    'dni' => $validatedData['dni'] ?? $usuario->contacto->dni,
                    'telefono' => $validatedData['telefono_contacto'] ?? $usuario->contacto->telefono,
                    'email' => $validatedData['email_contacto'] ?? $usuario->contacto->email,
                    'nombre_completo' => $validatedData['nombre_completo'] ?? $usuario->contacto->nombre_completo,
                ];
                
                $usuario->contacto->update($contactoActualizado);
                Log::info('🔧 Contacto actualizado:', $contactoActualizado);
            } else {
                Log::info('🔧 Creando nuevo contacto...');
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
                Log::info('🔧 Procesando nueva foto de perfil...');
                
                // Eliminar foto principal anterior si existe
                $fotoAnterior = $usuario->fotos()->where('es_principal', true)->first();
                if ($fotoAnterior) {
                    Log::info('🔧 Eliminando foto anterior:', ['ruta' => $fotoAnterior->ruta_foto]);
                    
                    // Eliminar archivo físico
                    if (Storage::disk('public')->exists($fotoAnterior->ruta_foto)) {
                        Storage::disk('public')->delete($fotoAnterior->ruta_foto);
                    }
                    // Eliminar registro
                    $fotoAnterior->delete();
                }

                $path = $request->file('foto_perfil')->store('perfiles', 'public');
                Log::info('🔧 Nueva foto guardada en:', ['ruta' => $path]);
                
                $usuario->fotos()->create([
                    'ruta_foto' => $path,
                    'es_principal' => true
                ]);
                Log::info('🔧 Foto de perfil actualizada en BD');
            } else {
                Log::info('🔧 No se envió nueva foto de perfil');
            }

            DB::commit();
            Log::info('✅ ===== USUARIO ACTUALIZADO EXITOSAMENTE =====');

            // Recargar relaciones actualizadas
            $usuario->load(['caracteristicas', 'contacto', 'fotos']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'usuario' => $usuario
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ ===== ERROR AL MODIFICAR USUARIO =====');
            Log::error('❌ Error message: ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al modificar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Actualizar solo datos opcionales
     */
    public function actualizarDatosOpcionales(Request $request)
    {
        DB::beginTransaction();
        
        try {
            Log::info('📝 Actualizando datos opcionales', $request->all());
            
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            $usuario = $user->userable;
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }
            
            // Validación de datos opcionales
            $validatedData = $request->validate([
                'ocupacion' => 'nullable|string|max:100',
                'tipoVivienda' => 'nullable|string|max:50',
                'experienciaMascotas' => 'nullable|string|max:50',
                'conviveConNiños' => 'nullable|string|max:10',
                'conviveConMascotas' => 'nullable|string|max:10',
                'descripcion' => 'nullable|string|max:500',
            ]);
            
            Log::info('📝 Datos validados para características', $validatedData);
            
            // Mapear nombres de campos del frontend a la base de datos
            $datosCaracteristicas = [
                'ocupacion' => $validatedData['ocupacion'] ?? null,
                'tipoVivienda' => $validatedData['tipoVivienda'] ?? null,
                'experiencia' => $validatedData['experienciaMascotas'] ?? null,
                'convivenciaNiños' => $validatedData['conviveConNiños'] ?? null,
                'convivenciaMascotas' => $validatedData['conviveConMascotas'] ?? null,
                'descripción' => $validatedData['descripcion'] ?? null,
            ];
            
            // Actualizar o crear características
            if ($usuario->caracteristicas) {
                $usuario->caracteristicas->update($datosCaracteristicas);
                Log::info('✅ Características actualizadas', $datosCaracteristicas);
            } else {
                CaracteristicasUsuario::create(array_merge($datosCaracteristicas, [
                    'usuario_id' => $usuario->id
                ]));
                Log::info('✅ Características creadas', $datosCaracteristicas);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Datos opcionales guardados exitosamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al guardar datos opcionales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar datos opcionales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar solo datos de contacto
     */
    public function actualizarDatosContacto(Request $request)
    {
        DB::beginTransaction();
        
        try {
            Log::info('📞 Actualizando datos de contacto', $request->all());
            
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            $usuario = $user->userable;
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }
            
            // Validación de datos de contacto
            $validatedData = $request->validate([
                'dni' => 'nullable|string|max:20|unique:usuario_contacto,dni,' . ($usuario->contacto ? $usuario->contacto->id : 'NULL'),
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:100|unique:usuario_contacto,email,' . ($usuario->contacto ? $usuario->contacto->id : 'NULL'),
                'nombre_completo' => 'nullable|string|max:200',
            ]);
            
            Log::info('📞 Datos validados para contacto', $validatedData);
            
            // Mapear nombres de campos del frontend a la base de datos
            $datosContacto = [
                'dni' => $validatedData['dni'] ?? null,
                'telefono' => $validatedData['telefono_contacto'] ?? null,
                'email' => $validatedData['email_contacto'] ?? null,
                'nombre_completo' => $validatedData['nombre_completo'] ?? null,
            ];
            
            // Actualizar o crear contacto
            if ($usuario->contacto) {
                $usuario->contacto->update($datosContacto);
                Log::info('✅ Contacto actualizado', $datosContacto);
            } else {
                ContactoUsuario::create(array_merge($datosContacto, [
                    'usuario_id' => $usuario->id
                ]));
                Log::info('✅ Contacto creado', $datosContacto);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Datos de contacto guardados exitosamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al guardar datos de contacto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                    'message' => 'Error al guardar datos de contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}