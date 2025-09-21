<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UbicacionUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserLocationController extends Controller
{
    public function store(Request $request)
    {
        Log::info('📍 Endpoint /guardar-ubicacion alcanzado', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // Verificar autenticación
            $user = $request->user();
            if (!$user) {
                Log::error('Usuario no autenticado en guardar-ubicacion');
                return response()->json(['message' => 'No autenticado'], 401);
            }

            Log::info('Usuario autenticado', ['user_id' => $user->id]);

            // Validación
            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0'
            ]);

            Log::debug('Datos validados', $validated);

            // Preparar datos base
            $locationData = [
                'user_id' => $user->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'location_updated_at' => now()
            ];

            // 🔥 SOLUCIÓN PARA POSTGRESQL - Usar consulta directa
            if (config('database.default') === 'pgsql') {
                // Usar una consulta SQL directa para PostgreSQL con PostGIS
                DB::insert(
                    "INSERT INTO user_locations (user_id, latitude, longitude, accuracy, location, location_updated_at, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, NOW(), NOW())",
                    [
                        $user->id,
                        $validated['latitude'],
                        $validated['longitude'],
                        $validated['accuracy'] ?? null,
                        $validated['longitude'], // para ST_MakePoint (longitude primero)
                        $validated['latitude'],  // para ST_MakePoint (latitude después)
                        now()
                    ]
                );
                
                // Obtener el ID del último insert
                $locationId = DB::getPdo()->lastInsertId();
                $location = UbicacionUsuario::find($locationId);

            } else {
                // Para MySQL/SQLite y otros databases
                $location = UbicacionUsuario::create($locationData);
            }

            Log::info('Ubicación guardada exitosamente', [
                'location_id' => $location->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Ubicación guardada correctamente',
                'data' => $location
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al guardar ubicación', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}