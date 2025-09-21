<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\Administrador;
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
                return redirect('http://localhost:5173/login?error=no_code');
            }

            $token = $client->fetchAccessTokenWithAuthCode($request->code);

            if (isset($token['error'])) {
                Log::error('Google Token Error: ' . $token['error']);
                return redirect('http://localhost:5173/login?error=auth_failed');
            }

            $client->setAccessToken($token);
            $oauth = new \Google\Service\Oauth2($client);
            $googleUser = $oauth->userinfo->get();

            // Buscar en la tabla users (autenticación principal)
            $authUser = User::where('email', $googleUser->email)->first();

            if ($authUser) {
                Auth::login($authUser);
                $sanctumToken = $authUser->createToken('web')->plainTextToken;

                // ✅ REDIRECCIÓN SEGÚN TIPO DE USUARIO (ACTUALIZADO)
                $redirectUrl = $this->getRedirectUrlByUserType($authUser);
                return redirect($redirectUrl . '?token=' . $sanctumToken . '&user_id=' . $authUser->id);
            }

            // Usuario nuevo: preparar datos temporales
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
            return redirect('http://localhost:5173/login?error=server_error');
        }
    }

    // ✅ NUEVO MÉTODO PARA REDIRECCIÓN
    private function getRedirectUrlByUserType($user)
    {
        if ($user->isAdministrador()) {
            return 'http://localhost:5173/administradores';
        } elseif ($user->isVeterinario()) {
            return 'http://localhost:5173/veterinarios/busqueda';
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
                    $specificUser = Veterinario::create([
                        'nombre_completo' => $validated['nombre'],
                        'google_id' => $validated['google_id'],
                        'matricula' => $validated['matricula'],
                        'especialidad' => $validated['especialidad'],
                        'foto' => $request->foto_perfil,
                    ]);
                    $userableType = 'App\Models\Veterinario';
                    break;

                case 'administrador':
                    $specificUser = Administrador::create([
                        'nombre_completo' => $validated['nombre'],
                        'nivel_acceso' => $validated['nivel_acceso'],
                        'google_id' => $validated['google_id'],
                        'user_type' => 'admin', // ✅ Valor por defecto para admin
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
                    'type' => $validated['user_type']
                ],
                'token' => $token,
                'redirect_url' => $this->getRedirectUrlByUserType($authUser) // ✅ Usar el mismo método
            ]);

        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

