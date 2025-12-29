<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 


class SetAuditVariables
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Establecer variables de sesión para auditoría
            $userId = auth()->id() ?? 'NULL';
            $ipAddress = $request->ip() ?? '';
            $userAgent = $request->userAgent() ?? '';
            
            // Escapar comillas simples para evitar errores SQL
            $userAgent = str_replace("'", "''", $userAgent);
            
            DB::statement("SET app.user_id = '{$userId}'");
            DB::statement("SET app.ip_address = '{$ipAddress}'");
            DB::statement("SET app.user_agent = '{$userAgent}'");
            
        } catch (\Exception $e) {
            // Silenciar errores para no interrumpir el flujo
            Log::warning('Error al establecer variables de auditoría: ' . $e->getMessage());
        }
        
        return $next($request);
    }
}