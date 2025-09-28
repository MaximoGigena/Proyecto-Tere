<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\Administrador;
use App\Models\SolicitudVeterinario;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            $client = new GoogleClient();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
            $client->addScope('email');
            $client->addScope('profile');

            $authUrl = $client->createAuthUrl();
            return redirect()->away($authUrl);

        } catch (\Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());
            return redirect('http://localhost:5173/login?error=server_error');
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $client = new GoogleClient();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

            if (!$request->has('code')) {
                Log::error('Google Callback: No code parameter');
                return redirect('http://localhost:5173/login?error=no_code');
            }

            $token = $client->fetchAccessTokenWithAuthCode($request->code);

            if (isset($token['error'])) {
                Log::error('Google Token Error: ' . json_encode($token));
                return redirect('http://localhost:5173/login?error=auth_failed');
            }

            $client->setAccessToken($token);
            $oauth = new \Google\Service\Oauth2($client);
            $googleUser = $oauth->userinfo->get();

            Log::info('Google User Info: ', [
                'email' => $googleUser->email,
                'id' => $googleUser->id,
                'name' => $googleUser->name
            ]);

            // Buscar usuario existente
            $authUser = User::where('email', $googleUser->email)->first();

            if ($authUser) {
                Auth::login($authUser);
                $sanctumToken = $authUser->createToken('web')->plainTextToken;

                // ✅ ACTUALIZADO: Redirigir según el estado del usuario/veterinario
                $redirectUrl = $this->getRedirectUrlByUserAndStatus($authUser);
                return redirect($redirectUrl . '#token=' . $sanctumToken . '&user_id=' . $authUser->id);
            }

            // Usuario nuevo
            $tempUserData = [
                'email' => $googleUser->email,
                'nombre' => $googleUser->name,
                'google_id' => $googleUser->id,
                'foto_perfil' => $googleUser->picture,
                'is_temp' => true,
            ];

            $userDataEncoded = base64_encode(json_encode($tempUserData));
            return redirect('http://localhost:5173/seleccionarRegistro?user_data=' . urlencode($userDataEncoded));

        } catch (\Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect('http://localhost:5173/login?error=server_error');
        }
    }

    /**
     * @param \App\Models\User $user
     * @return string
     */
    private function getRedirectUrlByUserAndStatus(User $user)
    {
        if ($user->isAdministrador()) {
            return 'http://localhost:5173/administradores';
        } elseif ($user->isVeterinario()) {
            // Obtener el modelo veterinario asociado
            $veterinario = $user->userable;
            
            if ($veterinario) {
                switch ($veterinario->estado) {
                    case Veterinario::ESTADO_PENDIENTE:
                        return 'http://localhost:5173/veterinario-pendiente';
                    
                    case Veterinario::ESTADO_APROBADO:
                        return 'http://localhost:5173/veterinarios/busqueda';
                    
                    case Veterinario::ESTADO_RECHAZADO:
                        return 'http://localhost:5173/veterinario-rechazado';
                    
                    default:
                        return 'http://localhost:5173/veterinario-pendiente';
                }
            }
            
            // Si no se encuentra el veterinario, redirigir a pendiente
            return 'http://localhost:5173/veterinario-pendiente';
        } else {
            return 'http://localhost:5173/explorar/encuentros';
        }
    }

    // app/Http/Controllers/GoogleAuthController.php
    public function completeRegistration(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'google_id' => 'required|string',
                'user_type' => 'required|string|in:usuario,veterinario,administrador',
                'especialidad' => 'required_if:user_type,veterinario',
                'matricula' => 'required_if:user_type,veterinario|unique:veterinarios,matricula',
                'nivel_acceso' => 'required_if:user_type,administrador',
            ]);

            // ✅ CREACIÓN SEGÚN TIPO DE USUARIO (ACTUALIZADO)
            switch ($validated['user_type']) {
                case 'usuario':
                    $specificUser = Usuario::create([
                        'nombre' => $validated['nombre'],
                        'user_type' => 'free',
                        'google_id' => $validated['google_id'],
                        'foto_perfil' => $request->foto_perfil,
                    ]);
                    $userableType = 'App\Models\Usuario';
                    break;

                case 'veterinario':
                    // Crear veterinario con estado pendiente
                    $specificUser = Veterinario::create([
                        'nombre_completo' => $validated['nombre'],
                        'google_id' => $validated['google_id'],
                        'matricula' => $validated['matricula'],
                        'especialidad' => $validated['especialidad'],
                        'foto' => $request->foto_perfil,
                        'estado' => Veterinario::ESTADO_PENDIENTE, // Estado inicial pendiente
                        'activo' => false, // Inactivo hasta ser aprobado
                    ]);
                    $userableType = 'App\Models\Veterinario';
                    
                    // ✅ Crear también la solicitud para el administrador
                    $this->crearSolicitudVeterinario($specificUser, $validated);
                    break;

                case 'administrador':
                    $specificUser = Administrador::create([
                        'nombre_completo' => $validated['nombre'],
                        'nivel_acceso' => $validated['nivel_acceso'],
                        'google_id' => $validated['google_id'],
                        'user_type' => 'admin',
                        'activo' => true,
                    ]);
                    $userableType = 'App\Models\Administrador';
                    break;
            }

            // ✅ Crear usuario de autenticación
            $authUser = User::create([
                'email' => $validated['email'],
                'password' => Hash::make(uniqid()),
                'userable_type' => $userableType,
                'userable_id' => $specificUser->id,
                'estado' => ($validated['user_type'] === 'veterinario') ? 'pendiente' : 'activo'
            ]);

            // Autenticar y generar token
            Auth::login($authUser);
            $token = $authUser->createToken('web')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $authUser->id,
                    'email' => $authUser->email,
                    'nombre' => $authUser->nombre,
                    'type' => $validated['user_type'],
                    'estado' => ($validated['user_type'] === 'veterinario') ? 'pendiente' : 'activo'
                ],
                'token' => $token,
                'redirect_url' => $this->getRedirectUrlByUserAndStatus($authUser) // ✅ Usar el método actualizado
            ]);

        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ NUEVO MÉTODO: Crear solicitud para veterinarios
    private function crearSolicitudVeterinario($veterinario, $data)
    {
        try {
            SolicitudVeterinario::create([
                'nombre_completo' => $veterinario->nombre_completo,
                'email' => $data['email'], // Email del usuario
                'matricula' => $veterinario->matricula,
                'especialidad' => $veterinario->especialidad,
                'anos_experiencia' => 0, // Puedes añadir este campo al formulario si lo necesitas
                'descripcion' => null, // Puedes añadir este campo al formulario
                'telefono' => null, // Puedes añadir este campo al formulario
                'email_contacto' => $data['email'],
                'fotos' => $veterinario->foto ? [$veterinario->foto] : [],
                'estado' => SolicitudVeterinario::ESTADO_PENDIENTE,
                'fecha_solicitud' => now()
            ]);

            Log::info('Solicitud creada para veterinario: ' . $veterinario->id);
            
        } catch (\Exception $e) {
            Log::error('Error creando solicitud para veterinario: ' . $e->getMessage());
            // No lanzar excepción para no interrumpir el registro principal
        }
    }

    // ✅ NUEVO MÉTODO: Verificar estado del veterinario (para uso en frontend)
    public function verificarEstadoVeterinario(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            if (!$user->isVeterinario()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no es un veterinario'
                ], 400);
            }

            $veterinario = $user->userable;
            
            return response()->json([
                'success' => true,
                'estado' => $veterinario->estado,
                'redirect_url' => $this->getRedirectUrlByUserAndStatus($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Error verificando estado del veterinario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el estado'
            ], 500);
        }
    }
}

