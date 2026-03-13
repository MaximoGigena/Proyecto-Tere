<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Mascota;
use App\Models\UbicacionUsuario;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class MascotaMatchingService
{

    /**
     * Puntajes por distancia
     */
    private const DISTANCE_SCORES = [
        'max_score' => 20,
        'thresholds' => [
            ['max_distance' => 1, 'score' => 20],   // Hasta 1km: 20 puntos
            ['max_distance' => 5, 'score' => 15],   // Hasta 5km: 15 puntos
            ['max_distance' => 10, 'score' => 10],  // Hasta 10km: 10 puntos
            ['max_distance' => 20, 'score' => 5],   // Hasta 20km: 5 puntos
            ['max_distance' => 50, 'score' => 2],   // Hasta 50km: 2 puntos
        ]
    ];



    /**
     * Calcula el puntaje de compatibilidad entre un usuario y una mascota
     */
    public function calculateCompatibilityScore(Usuario $usuario, Mascota $mascota, ?float $distance = null): int
    {
        $score = 0;
        
        // Verificar si el usuario tiene características
        if (!$usuario->caracteristicas) {
            return $score;
        }
        
        // Verificar si la mascota tiene características
        if (!$mascota->caracteristicas) {
            return $score;
        }
        
        $userChars = $usuario->caracteristicas;
        $petChars = $mascota->caracteristicas;

        // Solo calculamos si el usuario tiene datos opcionales
        if (!$this->userHasOptionalData($userChars)) {
            return $score;
        }

        // 1. Puntaje por ubicación/proximidad (20 pts)
        $score += $this->calculateLocationScore($usuario, $mascota, $distance);
        
        // 2. Tipo de vivienda vs Tamaño de mascota (20 pts)
        $score += $this->calculateHousingSizeScore($userChars->tipoVivienda, $petChars->tamano);
        
        // 3. Experiencia vs Energía (15 pts)
        $score += $this->calculateExperienceEnergyScore($userChars->experiencia, $petChars->energia);
        
        // 4. Ajuste por personalidad (dentro de experiencia)
        $score += $this->calculatePersonalityAdjustment($userChars->experiencia, $petChars->personalidad);
        
        // 5. Convive con niños vs Comportamiento con niños (20 pts)
        $score += $this->calculateChildrenCompatibility($userChars->convivenciaNiños, $petChars->comportamiento_ninos);
        
        // 6. Convive con mascotas vs Comportamiento con animales (15 pts)
        $score += $this->calculatePetsCompatibility($userChars->convivenciaMascotas, $petChars->comportamiento_animales);
        
        // 7. Afinidad general (10 pts)
        $score += $this->calculateGeneralAffinity($userChars, $petChars);
        
        // Asegurar que el score esté entre 0 y 100
        return max(0, min(100, $score));
    }

    /**
     * Calcula el puntaje basado en la proximidad geográfica
     */
    /**
     * Calcula el puntaje basado en la proximidad geográfica
     */
    private function calculateLocationScore(Usuario $usuario, Mascota $mascota, ?float $distance = null): int
    {
        // ✅ PRIORIDAD 1: Si ya tenemos la distancia calculada, usarla inmediatamente
        if ($distance !== null) {
            $score = $this->getScoreByDistance($distance);
            Log::info('📍 Puntaje por distancia (recibida):', [
                'distancia_km' => round($distance, 2),
                'puntaje' => $score,
                'mascota_id' => $mascota->id
            ]);
            return $score;
        }
        
        // ✅ PRIORIDAD 2: Intentar obtener ubicaciones si no tenemos distancia
        try {
            // Obtener la ubicación del usuario
            $userLocation = UbicacionUsuario::where('user_id', $usuario->user_id)
                ->orderBy('location_updated_at', 'desc')
                ->first();
            
            if (!$userLocation) {
                Log::warning('⚠️ Usuario sin ubicación:', ['usuario_id' => $usuario->id]);
                return 0;
            }
            
            // Obtener la ubicación del tutor de la mascota
            $petOwnerLocation = UbicacionUsuario::where('user_id', $mascota->usuario->user_id)
                ->orderBy('location_updated_at', 'desc')
                ->first();
            
            if (!$petOwnerLocation) {
                Log::warning('⚠️ Tutor sin ubicación:', ['tutor_id' => $mascota->usuario->id]);
                return 0;
            }
            
            // Calcular distancia
            $calculatedDistance = $this->calculateDistance(
                $userLocation->latitude,
                $userLocation->longitude,
                $petOwnerLocation->latitude,
                $petOwnerLocation->longitude
            );
            
            $score = $this->getScoreByDistance($calculatedDistance);
            
            Log::info('📍 Puntaje por distancia (calculada):', [
                'distancia_km' => round($calculatedDistance, 2),
                'puntaje' => $score,
                'mascota_id' => $mascota->id
            ]);
            
            return $score;
            
        } catch (\Exception $e) {
            Log::error('❌ Error calculando ubicación:', [
                'error' => $e->getMessage(),
                'mascota_id' => $mascota->id
            ]);
            return 0;
        }
    }

    /**
     * Obtiene el puntaje según la distancia en km
     */
    private function getScoreByDistance(float $distanceKm): int
    {
        foreach (self::DISTANCE_SCORES['thresholds'] as $threshold) {
            if ($distanceKm <= $threshold['max_distance']) {
                return $threshold['score'];
            }
        }
        
        return 0; // Más de 50km: 0 puntos
    }

    /**
     * Calcula la distancia entre dos coordenadas usando la fórmula de Haversine
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radio de la Tierra en kilómetros
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Obtiene mascotas ordenadas por compatibilidad para un usuario
     */
    /**
     * Obtiene mascotas ordenadas por compatibilidad para un usuario
     * INCLUYE ORDENAMIENTO POR DISTANCIA
     */
    public function getCompatiblePets(Usuario $usuario, int $limit = 20): Collection
    {
        // Si el usuario no tiene características, devolver vacío
        if (!$usuario->caracteristicas) {
            return collect([]);
        }

        // Obtener ubicación del usuario
        $userLocation = UbicacionUsuario::where('user_id', $usuario->id)
            ->orderBy('location_updated_at', 'desc')
            ->first();

        $pets = Mascota::with('caracteristicas')
            ->where('estado', 'disponible')
            ->get()
            ->map(function ($pet) use ($usuario, $userLocation) {
                // Calcular distancia si tenemos ubicaciones
                $distance = null;
                $locationScore = 0;
                
                if ($userLocation) {
                    $petOwnerLocation = UbicacionUsuario::where('user_id', $pet->user_id)
                        ->orderBy('location_updated_at', 'desc')
                        ->first();
                    
                    if ($petOwnerLocation) {
                        $distance = $this->calculateDistance(
                            $userLocation->latitude,
                            $userLocation->longitude,
                            $petOwnerLocation->latitude,
                            $petOwnerLocation->longitude
                        );
                    }
                }
                
                // Calcular score total (incluyendo ubicación)
                $pet->compatibility_score = $this->calculateCompatibilityScore($usuario, $pet, $distance);
                $pet->distance_km = $distance ? round($distance, 1) : null;
                $pet->location_score = $distance ? $this->getScoreByDistance($distance) : 0;
                
                return $pet;
            })
            ->sortByDesc('compatibility_score')
            ->values();
        
        return $limit ? $pets->take($limit) : $pets;
    }

    /**
     * Verifica si el usuario tiene datos opcionales completados
     */
    private function userHasOptionalData($caracteristicas): bool
    {
        if (!$caracteristicas) {
            return false;
        }
        
        return !empty($caracteristicas->tipoVivienda) || 
               !empty($caracteristicas->experiencia) || 
               !empty($caracteristicas->convivenciaNiños) || 
               !empty($caracteristicas->convivenciaMascotas);
    }

    /**
     * Calcula puntaje por tipo de vivienda vs tamaño
     */
    private function calculateHousingSizeScore($tipoVivienda, $tamanoMascota): int
    {
        if (empty($tipoVivienda) || empty($tamanoMascota)) {
            return 0;
        }

        $scores = [
            'departamento' => [
                'pequeño' => 20,
                'mediano' => 10,
                'grande' => -10,
            ],
            'casa' => [
                'pequeño' => 15,
                'mediano' => 15,
                'grande' => 20,
            ],
            'campo' => [
                'pequeño' => 10,
                'mediano' => 15,
                'grande' => 20,
            ],
        ];

        return $scores[$tipoVivienda][$tamanoMascota] ?? 0;
    }

    /**
     * Calcula puntaje por experiencia vs energía
     */
    private function calculateExperienceEnergyScore($experiencia, $energia): int
    {
        if (empty($experiencia) || empty($energia)) {
            return 0;
        }

        $experiencia = strtolower($experiencia);
        $energia = strtolower($energia);
        
        // Normalizar valores
        $nivelExperiencia = $this->normalizeExperience($experiencia);
        $nivelEnergia = $this->normalizeEnergy($energia);
        
        $scores = [
            'baja' => [
                'baja' => 15,
                'media' => 5,
                'alta' => -10,
            ],
            'media' => [
                'baja' => 10,
                'media' => 15,
                'alta' => 5,
            ],
            'alta' => [
                'baja' => 5,
                'media' => 10,
                'alta' => 15,
            ],
        ];

        return $scores[$nivelExperiencia][$nivelEnergia] ?? 0;
    }

    /**
     * Ajuste por personalidad según experiencia
     */
    private function calculatePersonalityAdjustment($experiencia, $personalidad): int
    {
        if (empty($experiencia) || empty($personalidad)) {
            return 0;
        }

        $experiencia = strtolower($experiencia);
        $personalidad = strtolower($personalidad);
        
        $nivelExperiencia = $this->normalizeExperience($experiencia);
        
        // Personalidades complejas para usuarios sin experiencia
        $personalidadesComplejas = ['nervioso', 'territorial', 'dominante', 'protector'];
        
        if ($nivelExperiencia === 'baja' && in_array($personalidad, $personalidadesComplejas)) {
            return -5;
        }
        
        // Personalidades ideales para primerizos
        $personalidadesIdeales = ['tranquilo', 'amigable', 'sociable'];
        
        if ($nivelExperiencia === 'baja' && in_array($personalidad, $personalidadesIdeales)) {
            return 5;
        }
        
        return 0;
    }

    /**
     * Calcula compatibilidad con niños
     */
    private function calculateChildrenCompatibility($conviveConNiños, $comportamientoNinos): int
    {
        if ($conviveConNiños !== 'si' || empty($comportamientoNinos)) {
            return 0;
        }
        
        $comportamiento = strtolower($comportamientoNinos);
        
        $scores = [
            'paciente' => 20,
            'juguetón' => 15,
            'temeroso' => -10,
            'estresado' => -20,
            'agresivo' => -20,
        ];

        return $scores[$comportamiento] ?? 0;
    }

    /**
     * Calcula compatibilidad con otras mascotas
     */
    private function calculatePetsCompatibility($conviveConMascotas, $comportamientoAnimales): int
    {
        if ($conviveConMascotas !== 'si' || empty($comportamientoAnimales)) {
            return 0;
        }
        
        $comportamiento = strtolower($comportamientoAnimales);
        
        $scores = [
            'social' => 15,
            'territorial' => -10,
            'agresivo' => -20,
            'depredador' => -15,
        ];

        return $scores[$comportamiento] ?? 0;
    }

    /**
     * Calcula afinidad general (bonificaciones adicionales)
     */
    private function calculateGeneralAffinity($userChars, $petChars): int
    {
        $affinity = 0;
        
        // Coincidencia en tipo de alimentación (si ambos lo especificaron)
        if (!empty($userChars->preferencia_alimentacion) && !empty($petChars->alimentacion)) {
            if ($userChars->preferencia_alimentacion === $petChars->alimentacion) {
                $affinity += 5;
            }
        }
        
        // Usuario con experiencia alta y mascota que necesita cuidados especiales
        $experiencia = strtolower($userChars->experiencia ?? '');
        if ($experiencia === 'alta' && $this->needsSpecialCare($petChars)) {
            $affinity += 5;
        }
        
        return $affinity;
    }

    /**
     * Normaliza el nivel de experiencia
     */
    private function normalizeExperience($experiencia): string
    {
        $map = [
            'nueva' => 'baja',
            'poca' => 'baja',
            'media' => 'media',
            'alta' => 'alta',
        ];
        
        return $map[$experiencia] ?? 'media';
    }

    /**
     * Normaliza el nivel de energía
     */
    private function normalizeEnergy($energia): string
    {
        $map = [
            'bajo' => 'baja',
            'medio' => 'media',
            'alto' => 'alta',
        ];
        
        return $map[$energia] ?? 'media';
    }

    /**
     * Verifica si la mascota necesita cuidados especiales
     */
    private function needsSpecialCare($petChars): bool
    {
        $personalidadesEspeciales = ['nervioso', 'territorial', 'protector'];
        $comportamientosEspeciales = ['agresivo', 'depredador'];
        
        return in_array(strtolower($petChars->personalidad ?? ''), $personalidadesEspeciales) ||
               in_array(strtolower($petChars->comportamiento_animales ?? ''), $comportamientosEspeciales) ||
               ($petChars->alimentacion ?? '') === 'Dietas especiales';
    }
}