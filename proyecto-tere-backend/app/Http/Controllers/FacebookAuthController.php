<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\Administrador;
use App\Models\SolicitudVeterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class FacebookAuthController extends Controller
{
    public function redirectToFacebook()
    {
        try {
            return Socialite::driver('facebook')->redirect();
        } catch (Exception $e) {
            Log::error('Facebook Redirect Error: ' . $e->getMessage());
            return redirect('http://localhost:5173/login?error=server_error');
        }
    }

    public function handleFacebookCallback(Request $request)
    {
        try {
            // ✅ AGREGAR DEBUG PARA VER PARÁMETROS DE REQUEST
            Log::info('Facebook Callback Request: ', [
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error'),
                'error' => $request->get('error'),
                'error_reason' => $request->get('error_reason'),
                'error_description' => $request->get('error_description'),
                'full_url' => $request->fullUrl()
            ]);

            // Si hay error de Facebook, loggearlo
            if ($request->has('error')) {
                Log::error('Facebook OAuth Error: ', [
                    'error' => $request->get('error'),
                    'error_reason' => $request->get('error_reason'),
                    'error_description' => $request->get('error_description')
                ]);
                
                return redirect('http://localhost:5173/login?error=facebook_oauth_failed&message=' . 
                            urlencode($request->get('error_description')));
            }

            $facebookUser = Socialite::driver('facebook')->user();

            Log::info('Facebook User Info: ', [
                'email' => $facebookUser->getEmail(),
                'id' => $facebookUser->getId(),
                'name' => $facebookUser->getName(),
                'avatar' => $facebookUser->getAvatar()
            ]);

            // Buscar usuario existente por email O por facebook_id
            $authUser = User::where('email', $facebookUser->getEmail())
                        ->orWhere('facebook_id', $facebookUser->getId())
                        ->first();

            // ✅ BUSCAR USUARIO EXISTENTE - MEJORADO
            $authUser = User::where('email', $facebookUser->getEmail())
                        ->orWhere('facebook_id', $facebookUser->getId())
                        ->first();

            if ($authUser) {
                // ✅ ACTUALIZAR facebook_id si viene por email
                if (!$authUser->facebook_id) {
                    $authUser->update(['facebook_id' => $facebookUser->getId()]);
                }

                // ✅ ACTUALIZAR avatar si está vacío
                if (!$authUser->avatar && $facebookUser->getAvatar()) {
                    $authUser->update(['avatar' => $facebookUser->getAvatar()]);
                }

                Auth::login($authUser);
                $sanctumToken = $authUser->createToken('web')->plainTextToken;

                // ✅ USAR EL MISMO MÉTODO QUE GOOGLE PARA REDIRECCIÓN
                $redirectUrl = $this->getRedirectUrlByUserAndStatus($authUser);
                
                Log::info('Usuario existente encontrado - Redirigiendo a: ' . $redirectUrl, [
                    'user_id' => $authUser->id,
                    'user_type' => $authUser->userable_type,
                    'estado' => $authUser->estado,
                    'is_veterinario' => $authUser->isVeterinario(),
                    'veterinario_estado' => $authUser->isVeterinario() ? $authUser->userable->estado : 'N/A'
                ]);
                
                return redirect($redirectUrl . '#token=' . $sanctumToken . '&user_id=' . $authUser->id);
            }

            // ✅ USUARIO NUEVO - MISMA LÓGICA QUE GOOGLE
            $tempUserData = [
                'email' => $facebookUser->getEmail(),
                'nombre' => $facebookUser->getName(),
                'facebook_id' => $facebookUser->getId(),
                'foto_perfil' => $facebookUser->getAvatar(),
                'is_temp' => true,
            ];

            $userDataEncoded = base64_encode(json_encode($tempUserData));
            return redirect('http://localhost:5173/seleccionarRegistro?user_data=' . urlencode($userDataEncoded));

        } catch (Exception $e) {
            Log::error('Facebook Callback Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect('http://localhost:5173/login?error=facebook_auth_failed&message=' . 
                        urlencode($e->getMessage()));
        }
    }

    /**
     * ✅ MÉTODO IDÉNTICO AL DE GOOGLE PARA COMPLETAR REGISTRO
     */
    public function completeRegistration(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'facebook_id' => 'required|string',
                'user_type' => 'required|string|in:usuario,veterinario,administrador',
                'especialidad' => 'required_if:user_type,veterinario',
                'matricula' => 'required_if:user_type,veterinario|unique:veterinarios,matricula',
                'nivel_acceso' => 'required_if:user_type,administrador',
            ]);

            // ✅ CREACIÓN SEGÚN TIPO DE USUARIO (MISMA LÓGICA QUE GOOGLE)
            switch ($validated['user_type']) {
                case 'usuario':
                    $specificUser = Usuario::create([
                        'nombre' => $validated['nombre'],
                        'user_type' => 'free',
                        'facebook_id' => $validated['facebook_id'],
                        'foto_perfil' => $request->foto_perfil,
                    ]);
                    $userableType = 'App\Models\Usuario';
                    break;

                case 'veterinario':
                    // Crear veterinario con estado pendiente
                    $specificUser = Veterinario::create([
                        'nombre_completo' => $validated['nombre'],
                        'facebook_id' => $validated['facebook_id'],
                        'matricula' => $validated['matricula'],
                        'especialidad' => $validated['especialidad'],
                        'foto' => $request->foto_perfil,
                        'estado' => Veterinario::ESTADO_PENDIENTE,
                        'activo' => false,
                    ]);
                    $userableType = 'App\Models\Veterinario';
                    
                    // ✅ Crear solicitud para el administrador
                    $this->crearSolicitudVeterinario($specificUser, $validated);
                    break;

                case 'administrador':
                    $specificUser = Administrador::create([
                        'nombre_completo' => $validated['nombre'],
                        'nivel_acceso' => $validated['nivel_acceso'],
                        'facebook_id' => $validated['facebook_id'],
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
                'redirect_url' => $this->getRedirectUrlByUserAndStatus($authUser)
            ]);

        } catch (Exception $e) {
            Log::error('Facebook Registration Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MÉTODO IDÉNTICO AL DE GOOGLE PARA REDIRECCIÓN
     */
    private function getRedirectUrlByUserAndStatus(User $user)
    {
        Log::info('Determinando redirección para usuario:', [
            'user_id' => $user->id,
            'user_type' => $user->userable_type,
            'estado' => $user->estado,
            'is_veterinario' => $user->isVeterinario(),
            'veterinario_exists' => $user->userable ? 'yes' : 'no'
        ]);

        if ($user->isAdministrador()) {
            $url = 'http://localhost:5173/administradores';
            Log::info('Redirigiendo administrador a: ' . $url);
            return $url;
            
        } elseif ($user->isVeterinario()) {
            $veterinario = $user->userable;
            
            if ($veterinario) {
                Log::info('Estado del veterinario:', [
                    'veterinario_id' => $veterinario->id,
                    'estado' => $veterinario->estado
                ]);
                
                switch ($veterinario->estado) {
                    case Veterinario::ESTADO_PENDIENTE:
                        $url = 'http://localhost:5173/veterinario-pendiente';
                        Log::info('Veterinario pendiente - Redirigiendo a: ' . $url);
                        return $url;
                    
                    case Veterinario::ESTADO_APROBADO:
                        $url = 'http://localhost:5173/veterinarios/busqueda';
                        Log::info('Veterinario aprobado - Redirigiendo a: ' . $url);
                        return $url;
                    
                    case Veterinario::ESTADO_RECHAZADO:
                        $url = 'http://localhost:5173/veterinario-rechazado';
                        Log::info('Veterinario rechazado - Redirigiendo a: ' . $url);
                        return $url;
                    
                    default:
                        $url = 'http://localhost:5173/veterinario-pendiente';
                        Log::info('Estado desconocido - Redirigiendo a: ' . $url);
                        return $url;
                }
            }
            
            Log::warning('Veterinario no encontrado para user_id: ' . $user->id);
            return 'http://localhost:5173/veterinario-pendiente';
            
        } else {
            $url = 'http://localhost:5173/explorar/encuentros';
            Log::info('Usuario normal - Redirigiendo a: ' . $url);
            return $url;
        }
    }

    /**
     * ✅ MÉTODO IDÉNTICO AL DE GOOGLE PARA CREAR SOLICITUD
     */
    private function crearSolicitudVeterinario($veterinario, $data)
    {
        try {
            SolicitudVeterinario::create([
                'nombre_completo' => $veterinario->nombre_completo,
                'email' => $data['email'],
                'matricula' => $veterinario->matricula,
                'especialidad' => $veterinario->especialidad,
                'anos_experiencia' => 0,
                'descripcion' => null,
                'telefono' => null,
                'email_contacto' => $data['email'],
                'fotos' => $veterinario->foto ? [$veterinario->foto] : [],
                'estado' => SolicitudVeterinario::ESTADO_PENDIENTE,
                'fecha_solicitud' => now()
            ]);

            Log::info('Solicitud Facebook creada para veterinario: ' . $veterinario->id);
            
        } catch (Exception $e) {
            Log::error('Error creando solicitud Facebook para veterinario: ' . $e->getMessage());
        }
    }

    /**
     * ✅ MÉTODO IDÉNTICO AL DE GOOGLE PARA VERIFICAR ESTADO
     */
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

        } catch (Exception $e) {
            Log::error('Error verificando estado del veterinario Facebook: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el estado'
            ], 500);
        }
    }
}