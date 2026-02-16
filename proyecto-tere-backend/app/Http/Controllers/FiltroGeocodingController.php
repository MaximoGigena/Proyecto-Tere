<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; 

class FiltroGeocodingController extends Controller
{
    /**
     * Obtener coordenadas de una ubicación usando Nominatim (OpenStreetMap)
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'ubicacion' => 'required|string|min:3|max:255',
            'limit' => 'sometimes|integer|min:1|max:10',
            'countrycodes' => 'sometimes|string|max:5',
        ]);

        $ubicacion = $request->input('ubicacion');
        $limit = $request->input('limit', 5);
        $countrycodes = $request->input('countrycodes', 'ar'); // Argentina por defecto

        // Crear clave única para cache
        $cacheKey = 'geocode_' . md5(strtolower($ubicacion) . '_' . $limit . '_' . $countrycodes);

        // Usar cache para evitar demasiadas solicitudes a la API
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($ubicacion, $limit, $countrycodes) {
            try {
                // API de Nominatim (OpenStreetMap)
                $response = Http::withHeaders([
                    'User-Agent' => config('app.name') . ' (adopciones@ejemplo.com)',
                    'Accept-Language' => 'es',
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $ubicacion . ', Argentina',
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => $limit,
                    'countrycodes' => $countrycodes,
                    'accept-language' => 'es',
                ]);

                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al conectar con el servicio de geocoding',
                        'results' => []
                    ], 500);
                }

                $results = $response->json();

                if (empty($results)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontraron resultados para la ubicación especificada',
                        'results' => []
                    ], 404);
                }

                // Formatear resultados
                $formattedResults = array_map(function ($result) {
                    return [
                        'display_name' => $result['display_name'],
                        'lat' => (float) $result['lat'],
                        'lon' => (float) $result['lon'],
                        'address' => $result['address'] ?? [],
                        'type' => $result['type'] ?? 'unknown',
                        'importance' => $result['importance'] ?? 0,
                    ];
                }, $results);

                return response()->json([
                    'success' => true,
                    'message' => 'Resultados encontrados',
                    'results' => $formattedResults
                ]);

            } catch (\Exception $e) {
                Log::error('Error en geocoding: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor',
                    'results' => []
                ], 500);
            }
        });
    }

    /**
     * Autocompletado de ubicaciones
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $query = $request->input('query');
        $limit = $request->input('limit', 5);

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . ' (adopciones@ejemplo.com)',
                'Accept-Language' => 'es',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query . ', Argentina',
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => $limit,
                'countrycodes' => 'ar',
                'accept-language' => 'es',
            ]);

            if (!$response->successful()) {
                return response()->json([], 200);
            }

            $results = $response->json();

            $suggestions = array_map(function ($result) {
                return [
                    'display' => $result['display_name'],
                    'value' => $result['display_name'],
                    'lat' => (float) $result['lat'],
                    'lon' => (float) $result['lon'],
                ];
            }, $results);

            return response()->json($suggestions);

        } catch (\Exception $e) {
            Log::error('Error en autocomplete: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    /**
     * Geocoding inverso (coordenadas a dirección)
     */
    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $lat = $request->input('lat');
        $lon = $request->input('lon');

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . ' (adopciones@ejemplo.com)',
                'Accept-Language' => 'es',
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lon,
                'format' => 'json',
                'addressdetails' => 1,
                'accept-language' => 'es',
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener la dirección',
                ], 500);
            }

            $result = $response->json();

            return response()->json([
                'success' => true,
                'address' => $result['display_name'] ?? '',
                'details' => $result['address'] ?? [],
                'lat' => $lat,
                'lon' => $lon,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en reverse geocoding: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}