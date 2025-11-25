<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ContactoUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class TelegramController extends Controller
{
    protected $telegramService;

     public function guardarChatId(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'telegram_chat_id' => 'required|string|max:50'
        ]);

        try {
            // Buscar el contacto por email
            $contacto = ContactoUsuario::where('email', $request->email)->first();

            if (!$contacto) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un usuario con ese email'
                ], 404);
            }

            // Guardar el chat ID (permitir actualización)
            $contacto->update([
                'telegram_chat_id' => $request->telegram_chat_id
            ]);

            Log::info("✅ Chat ID guardado/actualizado", [
                'usuario_id' => $contacto->usuario_id,
                'email' => $request->email,
                'chat_id' => $request->telegram_chat_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chat ID de Telegram guardado exitosamente',
                'data' => [
                    'usuario_id' => $contacto->usuario_id,
                    'nombre_completo' => $contacto->nombre_completo,
                    'telegram_chat_id' => $contacto->telegram_chat_id
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar chat ID de Telegram: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarChatIdPorEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            Log::info("🔍 Buscando chat ID por email: " . $request->email);

            // Buscar en ContactoUsuario primero
            $contacto = ContactoUsuario::where('email', $request->email)->first();

            if (!$contacto) {
                // Si no está en ContactoUsuario, buscar si el usuario existe
                $usuario = User::where('email', $request->email)->first();
                
                if ($usuario) {
                    Log::info("✅ Usuario existe pero no tiene contacto, creando registro...");
                    
                    // Crear registro básico en ContactoUsuario
                    $contacto = ContactoUsuario::create([
                        'usuario_id' => $usuario->id,
                        'email' => $request->email,
                        'nombre_completo' => $usuario->nombre
                        // telegram_chat_id se establecerá más tarde
                    ]);
                } else {
                    Log::warning("❌ No se encontró contacto ni usuario con email: " . $request->email);
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontró un usuario con ese email'
                    ], 404);
                }
            }

            if (!$contacto->telegram_chat_id) {
                Log::info("ℹ️ Email {$request->email} no tiene Telegram configurado");
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró chat ID de Telegram para este email'
                ], 404);
            }

            Log::info("✅ Chat ID encontrado por email", [
                'email' => $request->email,
                'chat_id' => $contacto->telegram_chat_id,
                'usuario_id' => $contacto->usuario_id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'telegram_chat_id' => $contacto->telegram_chat_id,
                    'nombre_completo' => $contacto->nombre_completo,
                    'email' => $contacto->email,
                    'usuario_id' => $contacto->usuario_id
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar chat ID por email: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
    public function obtenerChatId($usuarioId) // ✅ Ahora recibe el parámetro correctamente
    {
        try {
            Log::info("🔍 Buscando chat ID para usuario: $usuarioId");

            $contacto = ContactoUsuario::where('usuario_id', $usuarioId)->first();

            if (!$contacto) {
                Log::warning("❌ No se encontró contacto para usuario: $usuarioId");
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de contacto para este usuario'
                ], 404);
            }

            if (!$contacto->telegram_chat_id) {
                Log::info("ℹ️ Usuario $usuarioId no tiene Telegram configurado");
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró chat ID de Telegram para este usuario'
                ], 404);
            }

            Log::info("✅ Chat ID encontrado", [
                'usuario_id' => $usuarioId,
                'chat_id' => $contacto->telegram_chat_id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'telegram_chat_id' => $contacto->telegram_chat_id,
                    'nombre_completo' => $contacto->nombre_completo,
                    'email' => $contacto->email
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener chat ID: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function sendDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'chat_id' => 'required',
            'caption' => 'nullable|string|max:255'
        ]);

        try {
            // Guardar el archivo temporalmente
            $path = $request->file('document')->store('temp');
            $fullPath = Storage::path($path);

            // Enviar documento
            $result = $this->telegramService->sendDocument(
                $request->chat_id,
                $fullPath,
                $request->caption
            );

            // Limpiar archivo temporal
            Storage::delete($path);

            if ($result['ok']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document sent successfully!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $result['description']
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendStoredDocument($filename)
    {
        try {
            $chatId = config('telegram.chat_id');
            $result = $this->telegramService->sendDocumentFromStorage(
                $chatId,
                "documents/{$filename}",
                "Aquí tienes tu documento: {$filename}"
            );

            if ($result['ok']) {
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}