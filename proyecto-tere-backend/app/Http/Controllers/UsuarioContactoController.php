<?php

namespace App\Http\Controllers;

use App\Models\ContactoUsuario;
use App\Models\Usuario; // Asegúrate de importar el modelo Usuario si es necesario
use Illuminate\Http\Request;

class UsuarioContactoController extends Controller
{
    public function obtenerMedios($usuarioId)
    {
        // Verificar que el usuario existe
        $usuario = \App\Models\Usuario::find($usuarioId);
        
        if (!$usuario) {
            return response()->json([
                'success' => false, 
                'message' => 'Usuario no encontrado',
                'data' => []
            ], 404);
        }

        // Obtener contacto del usuario
        $contacto = ContactoUsuario::where('usuario_id', $usuarioId)->first();

        if (!$contacto) {
            return response()->json([
                'success' => true, 
                'message' => 'No se encontraron medios de contacto',
                'data' => []
            ]);
        }

        $medios = [];

        // Email
        if ($contacto->email && filter_var($contacto->email, FILTER_VALIDATE_EMAIL)) {
            $medios[] = [
                'id' => 'email', 
                'nombre' => 'Email', 
                'valor' => $contacto->email,
                'icono' => 'email'
            ];
        }

        // WhatsApp (teléfono)
        if ($contacto->telefono && $this->esTelefonoValido($contacto->telefono)) {
            $medios[] = [
                'id' => 'whatsapp', 
                'nombre' => 'WhatsApp', 
                'valor' => $contacto->telefono,
                'icono' => 'whatsapp'
            ];
        }

        // Telegram (id del chat)
        if ($contacto->telegram_chat_id) {
            $medios[] = [
                'id' => 'telegram', 
                'nombre' => 'Telegram', 
                'valor' => $contacto->telegram_chat_id,
                'icono' => 'telegram',
                'tipo' => 'telegram',
                'configurado' => true
            ];
        } else {
            // Mostrar opción para configurar Telegram si no tiene chat ID
            $medios[] = [
                'id' => 'telegram', 
                'nombre' => 'Telegram', 
                'valor' => null,
                'icono' => 'telegram',
                'tipo' => 'telegram',
                'configurado' => false,
                'mensaje' => 'Haz click aquí para configurar Telegram'
            ];
        }

        return response()->json([
            'success' => true, 
            'data' => $medios,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $contacto->nombre_completo ?: 'Usuario'
            ]
        ]);
    }

    private function esTelefonoValido($telefono)
    {
        // Limpiar el teléfono de caracteres no numéricos
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        
        // Verificar que tenga entre 8 y 15 dígitos
        return strlen($telefonoLimpio) >= 8 && strlen($telefonoLimpio) <= 15;
    }
}
