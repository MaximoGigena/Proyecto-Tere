<?php

namespace App\Http\Controllers;

use App\Models\ContactoUsuario;
use App\Models\Usuario;
use App\Models\User; // ← Importar modelo User
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsuarioContactoController extends Controller
{
    public function obtenerMedios($usuarioId)
    {
        try {
            // Verificar que el usuario existe
            $usuario = Usuario::find($usuarioId);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Usuario no encontrado',
                    'data' => []
                ], 404);
            }

            // Obtener el User asociado a este Usuario
            $user = User::where('userable_type', Usuario::class)
                       ->where('userable_id', $usuarioId)
                       ->first();

            // Obtener contacto del usuario (para email, teléfono, etc.)
            $contacto = ContactoUsuario::where('usuario_id', $usuarioId)->first();

            $medios = [];

            // Email (desde ContactoUsuario)
            if ($contacto && $contacto->email && filter_var($contacto->email, FILTER_VALIDATE_EMAIL)) {
                $medios[] = [
                    'id' => 'email', 
                    'nombre' => 'Email', 
                    'valor' => $contacto->email,
                    'icono' => 'email'
                ];
            }

            // WhatsApp / Teléfono (desde ContactoUsuario)
            if ($contacto && $contacto->telefono && $this->esTelefonoValido($contacto->telefono)) {
                $medios[] = [
                    'id' => 'whatsapp', 
                    'nombre' => 'WhatsApp', 
                    'valor' => $contacto->telefono,
                    'icono' => 'whatsapp'
                ];
            }

            // Telegram - AHORA DESDE EL MODELO USER
            if ($user && $user->telegram_chat_id) {
                // Usuario tiene Telegram configurado
                $nombreTelegram = $user->telegram_username 
                                ? '@' . $user->telegram_username 
                                : $user->getTelegramFullNameAttribute();
                
                $medios[] = [
                    'id' => 'telegram', 
                    'nombre' => 'Telegram', 
                    'valor' => $nombreTelegram ?: $user->telegram_chat_id,
                    'icono' => 'telegram',
                    'tipo' => 'telegram',
                    'configurado' => true,
                    'chat_id' => $user->telegram_chat_id // Guardar el chat_id para enviar mensajes
                ];
                
                Log::info('✅ Telegram encontrado para usuario', [
                    'usuario_id' => $usuarioId,
                    'chat_id' => $user->telegram_chat_id,
                    'username' => $user->telegram_username
                ]);
            } else {
                // Usuario NO tiene Telegram configurado
                $medios[] = [
                    'id' => 'telegram', 
                    'nombre' => 'Telegram', 
                    'valor' => null,
                    'icono' => 'telegram',
                    'tipo' => 'telegram',
                    'configurado' => false,
                    'mensaje' => 'Haz click aquí para configurar Telegram',
                    'enlace_configuracion' => '/configurar-telegram' // URL para configuración
                ];
                
                Log::info('ℹ️ Usuario sin Telegram configurado', [
                    'usuario_id' => $usuarioId
                ]);
            }

            // Log para depuración
            Log::info('📡 Medios de contacto obtenidos', [
                'usuario_id' => $usuarioId,
                'total_medios' => count($medios),
                'medios' => array_column($medios, 'nombre')
            ]);

            return response()->json([
                'success' => true, 
                'data' => $medios,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $contacto ? ($contacto->nombre_completo ?: $usuario->nombre) : $usuario->nombre
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener medios de contacto:', [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Error al obtener medios de contacto',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    private function esTelefonoValido($telefono)
    {
        // Limpiar el teléfono de caracteres no numéricos
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        
        // Verificar que tenga entre 8 y 15 dígitos
        return strlen($telefonoLimpio) >= 8 && strlen($telefonoLimpio) <= 15;
    }
}