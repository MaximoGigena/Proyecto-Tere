<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UbicacionUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

            // 🔥 NUEVO: Obtener datos de reverse geocoding con Nominatim
            $geoData = $this->getReverseGeocodingData(
                $validated['latitude'],
                $validated['longitude']
            );

            Log::debug('Datos de geocoding obtenidos', $geoData);

            // ✅ CORREGIDO: Usar user_id en lugar de usuario_id
            $locationData = [
                'user_id' => $user->id, // ← CORRECTO: user_id
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'location_updated_at' => now(),
                // Datos obtenidos del reverse geocoding
                'country' => $geoData['country'] ?? null,
                'country_code' => $geoData['country_code'] ?? null,
                'state' => $geoData['state'] ?? null,
                'city' => $geoData['city'] ?? null,
                'source' => 'gps'
            ];

            // Para PostgreSQL
            if (config('database.default') === 'pgsql') {
                // ✅ CORREGIDO: Usar user_id en la consulta SQL
                DB::insert(
                    "INSERT INTO user_locations (user_id, latitude, longitude, accuracy, location, 
                    location_updated_at, country, country_code, state, city, source, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [
                        $user->id, // ← user_id
                        $validated['latitude'],
                        $validated['longitude'],
                        $validated['accuracy'] ?? null,
                        $validated['longitude'],
                        $validated['latitude'],
                        now(),
                        $geoData['country'] ?? null,
                        $geoData['country_code'] ?? null,
                        $geoData['state'] ?? null,
                        $geoData['city'] ?? null,
                        'gps'
                    ]
                );
                
                $locationId = DB::getPdo()->lastInsertId();
                $location = UbicacionUsuario::find($locationId);

            } else {
                // Para MySQL/SQLite
                $location = UbicacionUsuario::create($locationData);
            }

            Log::info('Ubicación guardada exitosamente', [
                'location_id' => $location->id,
                'user_id' => $user->id,
                'city' => $geoData['city'] ?? 'N/A',
                'state' => $geoData['state'] ?? 'N/A'
            ]);

            return response()->json([
                'message' => 'Ubicación guardada correctamente',
                'data' => $location,
                'geo_data' => $geoData
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

    private function parseArgentinianAddress(array $data): array
    {
        $address = $data['address'] ?? [];
        $displayName = $data['display_name'] ?? '';
        
        // 1. Prioridad para Córdoba (tu provincia)
        if (str_contains(strtolower($displayName), 'córdoba') || 
            str_contains(strtolower($displayName), 'cordoba')) {
            
            // Buscar patrones de ciudades de Córdoba
            $cordobaCities = ['tanti', 'carlos paz', 'villa general belgrano', 'alta gracia', 'cosquín', 'la cumbre', 'villa dolores'];
            
            foreach ($cordobaCities as $city) {
                if (str_contains(strtolower($displayName), $city)) {
                    return [
                        'country' => 'Argentina',
                        'country_code' => 'AR',
                        'state' => 'Córdoba',
                        'city' => ucwords($city),
                        'display_name' => $displayName,
                        'source' => 'nominatim_cordoba'
                    ];
                }
            }
        }
        
        // 2. Extraer ciudad con lógica jerárquica para Argentina
        $city = null;
        
        // Jerarquía de campos para ciudad en Argentina
        $cityFields = [
            'city', 'town', 'village', 'municipality', 
            'hamlet', 'locality', 'suburb', 'neighbourhood',
            'county', 'city_district'
        ];
        
        foreach ($cityFields as $field) {
            if (!empty($address[$field])) {
                $city = $address[$field];
                break;
            }
        }
        
        // 3. Si no hay ciudad pero hay county, usarlo
        if (empty($city) && !empty($address['county'])) {
            $city = $address['county'];
        }
        
        // 4. Extraer provincia
        $state = $address['state'] ?? $address['province'] ?? $address['region'] ?? null;
        
        // 5. Si estamos en Argentina, normalizar nombres
        if (str_contains(strtolower($displayName), 'argentina')) {
            if ($state && str_contains(strtolower($state), 'córdoba')) {
                $state = 'Córdoba';
            }
        }
        
        return [
            'country' => $address['country'] ?? null,
            'country_code' => strtoupper($address['country_code'] ?? ''),
            'state' => $state,
            'city' => $city,
            'display_name' => $displayName,
            'postcode' => $address['postcode'] ?? null,
            'road' => $address['road'] ?? null,
            'raw_address' => $address // Para debugging
        ];
    }
    
    /**
     * Obtiene datos de reverse geocoding usando Nominatim
     */
    private function getReverseGeocodingData(float $latitude, float $longitude): array
    {
        try {
            $cacheKey = "nominatim_ar_{$latitude}_{$longitude}";
            $cached = cache()->get($cacheKey);
            
            if ($cached) {
                return $cached;
            }
            
            // 🔥 CONFIGURACIÓN MEJORADA PARA ARGENTINA
            $response = Http::withHeaders([
                'User-Agent' => 'TereApp/1.0 (contacto@tereapp.com)',
                'Accept-Language' => 'es',
                'Referer' => 'https://tereapp.com', // Mejora aceptación
            ])->timeout(15)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 12, // Zoom más cercano para áreas rurales
                'addressdetails' => 1,
                'namedetails' => 1,
                'extratags' => 1,
                'polygon' => 0,
                'polygon_threshold' => 0.1,
                'accept-language' => 'es',
                'countrycodes' => 'ar', // Especificar Argentina
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // 🔥 ALGORITMO MEJORADO PARA DETECTAR CIUDAD EN ARGENTINA
                $geoData = $this->parseArgentinianAddress($data);
                
                // Si no encontramos ciudad, intentar con zoom más lejano
                if (empty($geoData['city'])) {
                    $geoData = $this->tryWithDifferentZoom($latitude, $longitude, $geoData);
                }
                
                // Guardar en cache por 30 días
                cache()->put($cacheKey, $geoData, now()->addDays(30));
                
                Log::debug('Geocoding data obtenido:', $geoData);
                return $geoData;
            }
            
        } catch (\Exception $e) {
            Log::warning('Error en reverse geocoding', [
                'error' => $e->getMessage(),
                'lat' => $latitude,
                'lon' => $longitude
            ]);
        }
        
        // Retornar datos vacíos si falla
        return $this->getDefaultGeoData();
    }

    // 🔥 NUEVO: Intentar con diferentes niveles de zoom
    private function tryWithDifferentZoom(float $latitude, float $longitude, array $currentData): array
    {
        try {
            // Intentar con zoom más lejano (menos detalle, pero puede capturar municipio)
            $response = Http::withHeaders([
                'User-Agent' => 'TereApp/1.0',
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 10, // Zoom más lejano
                'addressdetails' => 1,
                'countrycodes' => 'ar'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $address = $data['address'] ?? [];
                
                // Si encontramos ciudad con zoom lejano, usarla
                if (!empty($address['city']) || !empty($address['town']) || !empty($address['county'])) {
                    $currentData['city'] = $address['city'] ?? $address['town'] ?? $address['county'] ?? null;
                    $currentData['state'] = $address['state'] ?? $address['province'] ?? $currentData['state'];
                    $currentData['source'] = 'nominatim_zoom10';
                }
            }
        } catch (\Exception $e) {
            // Ignorar error
        }
        
        return $currentData;
    }

    private function getDefaultGeoData(): array
    {
        return [
            'country' => 'Argentina',
            'country_code' => 'AR',
            'state' => 'Córdoba',
            'city' => null,
            'display_name' => 'Ubicación no especificada'
        ];
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }
            
            // Obtener la ubicación más reciente
            $ubicacion = UbicacionUsuario::where('user_id', $user->id)
                ->orderBy('location_updated_at', 'desc')
                ->first();
            
            if (!$ubicacion) {
                return response()->json([
                    'message' => 'No se encontró ubicación registrada',
                    'data' => null
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $ubicacion->id,
                    'latitude' => $ubicacion->latitude,
                    'longitude' => $ubicacion->longitude,
                    'city' => $ubicacion->city,
                    'state' => $ubicacion->state,
                    'country' => $ubicacion->country,
                    'country_code' => $ubicacion->country_code,
                    'source' => $ubicacion->source,
                    'updated_at' => $ubicacion->location_updated_at,
                    'display_text' => implode(', ', array_filter([
                        $ubicacion->city,
                        $ubicacion->state,
                        $ubicacion->country
                    ]))
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo ubicación del usuario', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'no-authenticated'
            ]);
            
            return response()->json([
                'message' => 'Error al obtener la ubicación',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}