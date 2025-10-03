<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CerrarSesionController extends Controller
{
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                Log::info('Usuario cerrando sesión', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'userable_type' => $user->userable_type
                ]);
                
                // 🔥 Revocar solo el token actual
                $user->currentAccessToken()->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Sesión cerrada exitosamente'
                ], 200);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No hay usuario autenticado'
            ], 401);
            
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
