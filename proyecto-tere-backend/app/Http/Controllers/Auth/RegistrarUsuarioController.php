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
                    'email' => $validatedData['email_contacto'] ?? $validatedData['email'], // Usar email principal si no se proporciona uno de contacto
                    'nombre_completo' => $validatedData['nombre_completo'] ?? $validatedData['nombre'], // Usar nombre principal si no se proporciona nombre completo
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
                'access_token' => $token, // ENVIAR EL TOKEN
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}