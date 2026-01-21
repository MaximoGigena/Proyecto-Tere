<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ReverseGeocodingService
{
    /**
     * URL base de Nominatim
     */
    private $nominatimUrl = 'https://nominatim.openstreetmap.org/reverse';
    
    /**
     * User-Agent para las solicitudes (requerido por Nominatim)
     */
    private $userAgent = 'TereApp/1.0 (contacto@tereapp.com)';
    
    /**
     * Obtiene datos de dirección a partir de coordenadas
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getAddressData(float $latitude, float $longitude): array
    {
        // Crear una clave única para el cache
        $cacheKey = "geocoding_{$latitude}_{$longitude}";
        
        // Intentar obtener del cache primero (cache por 30 días para ubicaciones estáticas)
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            Log::debug('Datos de geocoding obtenidos del cache', [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            return $cachedData;
        }
        
        try {
            Log::debug('Solicitando reverse geocoding a Nominatim', [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            
            // Realizar la solicitud a Nominatim
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept-Language' => 'es', // Español para nombres de lugares
            ])->timeout(10) // Timeout de 10 segundos
              ->get($this->nominatimUrl, [
                'format' => 'jsonv2',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18, // Nivel de detalle
                'addressdetails' => 1, // Obtener detalles de dirección
                'namedetails' => 0,
                'extratags' => 0,
                'polygon' => 0
            ]);
            
            if ($response->failed()) {
                Log::warning('Error en respuesta de Nominatim', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return $this->getDefaultGeoData();
            }
            
            $data = $response->json();
            
            Log::debug('Respuesta de Nominatim recibida', $data);
            
            // Procesar la respuesta
            $geoData = $this->parseNominatimResponse($data);
            
            // Guardar en cache por 30 días
            Cache::put($cacheKey, $geoData, now()->addDays(30));
            
            return $geoData;
            
        } catch (\Exception $e) {
            Log::error('Error en reverse geocoding', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            
            return $this->getDefaultGeoData();
        }
    }
    
    /**
     * Procesa la respuesta de Nominatim
     *
     * @param array $response
     * @return array
     */
    private function parseNominatimResponse(array $response): array
    {
        $address = $response['address'] ?? [];
        
        return [
            'display_name' => $response['display_name'] ?? null,
            'country' => $address['country'] ?? null,
            'country_code' => strtoupper($address['country_code'] ?? ''),
            'state' => $address['state'] ?? $address['region'] ?? $address['province'] ?? null,
            'county' => $address['county'] ?? null,
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null,
            'postcode' => $address['postcode'] ?? null,
            'road' => $address['road'] ?? null,
            'house_number' => $address['house_number'] ?? null,
            'neighbourhood' => $address['neighbourhood'] ?? null,
            'suburb' => $address['suburb'] ?? null
        ];
    }
    
    /**
     * Datos por defecto en caso de error
     *
     * @return array
     */
    private function getDefaultGeoData(): array
    {
        return [
            'country' => null,
            'country_code' => null,
            'state' => null,
            'city' => null,
            'display_name' => 'Ubicación no especificada'
        ];
    }
    
    /**
     * Obtiene datos básicos de geocoding sin cache (para uso interno)
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getBasicAddressData(float $latitude, float $longitude): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->timeout(5)->get($this->nominatimUrl, [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 10
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $address = $data['address'] ?? [];
                
                return [
                    'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                    'state' => $address['state'] ?? $address['region'] ?? null,
                    'country' => $address['country'] ?? null
                ];
            }
        } catch (\Exception $e) {
            // Silenciar errores para esta versión básica
        }
        
        return [];
    }
}