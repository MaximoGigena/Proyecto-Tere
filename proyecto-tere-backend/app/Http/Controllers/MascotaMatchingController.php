<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mascota;
use App\Services\MascotaMatchingService;
use Illuminate\Http\Request;

class MascotaMatchingController extends Controller
{
    protected $matchingService;

    public function __construct(MascotaMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Obtiene mascotas recomendadas para el usuario autenticado
     */
    public function recommendedPets(Request $request)
    {
        $user = $request->user();
        
        $pets = $this->matchingService->getCompatiblePets($user, $request->get('limit', 20));
        
        return response()->json([
            'data' => $pets->map(function ($pet) {
                return [
                    'id' => $pet->id,
                    'nombre' => $pet->nombre,
                    'raza' => $pet->raza,
                    'edad' => $pet->edad,
                    'tamaño' => $pet->tamaño,
                    'compatibility_score' => $pet->compatibility_score,
                    'distance' => [
                        'km' => $pet->distance_km,
                        'score' => $pet->location_score,
                        'text' => $this->getDistanceText($pet->distance_km)
                    ]
                ];
            }),
            'meta' => [
                'total' => $pets->count(),
                'user_has_optional_data' => $this->userHasOptionalData($user),
                'user_has_location' => $this->userHasLocation($user)
            ]
        ]);
    }

    private function getDistanceText(?float $distanceKm): string
    {
        if ($distanceKm === null) {
            return 'Distancia no disponible';
        }
        
        if ($distanceKm < 1) {
            return 'Muy cerca (menos de 1km)';
        }
        
        if ($distanceKm < 5) {
            return 'Cerca (' . round($distanceKm, 1) . 'km)';
        }
        
        if ($distanceKm < 10) {
            return 'A poca distancia (' . round($distanceKm, 1) . 'km)';
        }
        
        if ($distanceKm < 20) {
            return 'Moderadamente lejos (' . round($distanceKm, 1) . 'km)';
        }
        
        return 'Lejos (' . round($distanceKm, 1) . 'km)';
    }

    private function userHasLocation($user)
    {
        return \App\Models\UbicacionUsuario::where('user_id', $user->id)->exists();
    }

    /**
     * Calcula compatibilidad con una mascota específica
     */
    public function checkCompatibility(Request $request, Mascota $mascota)
    {
        $user = $request->user();
        
        $score = $this->matchingService->calculateCompatibilityScore($user, $mascota);
        
        return response()->json([
            'data' => [
                'mascota_id' => $mascota->id,
                'compatibility_score' => $score,
                'compatibility_level' => $this->getCompatibilityLevel($score),
                'details' => $this->getCompatibilityDetails($user, $mascota)
            ]
        ]);
    }

    /**
     * Obtiene nivel de compatibilidad textual
     */
    private function getCompatibilityLevel($score)
    {
        if ($score >= 80) return 'Excelente';
        if ($score >= 60) return 'Buena';
        if ($score >= 40) return 'Regular';
        if ($score >= 20) return 'Baja';
        return 'Muy baja';
    }

    /**
     * Obtiene detalles de compatibilidad
     */
    private function getCompatibilityDetails($user, $mascota)
    {
        $details = [];
        
        if ($user->tipo_vivienda && $mascota->tamaño) {
            $details['housing'] = $this->getHousingAdvice($user->tipo_vivienda, $mascota->tamaño);
        }
        
        if ($user->convive_con_niños === 'si' && $mascota->comportamiento_niños) {
            $details['children'] = $this->getChildrenAdvice($mascota->comportamiento_niños);
        }
        
        return $details;
    }

    private function getHousingAdvice($vivienda, $tamaño)
    {
        if ($vivienda === 'departamento' && $tamaño === 'grande') {
            return 'Un perro grande en departamento puede necesitar mucho ejercicio';
        }
        return null;
    }

    private function getChildrenAdvice($comportamiento)
    {
        $advice = [
            'paciente' => 'Excelente con niños',
            'juguetón' => 'Muy juguetón con niños, supervisar',
            'temeroso' => 'Puede ser temeroso con niños, requerirá paciencia',
            'agresivo' => 'No recomendado para hogares con niños'
        ];
        
        return $advice[strtolower($comportamiento)] ?? null;
    }

    private function userHasOptionalData($user)
    {
        return !empty($user->tipo_vivienda) || 
               !empty($user->experiencia_mascotas) || 
               !empty($user->convive_con_niños) || 
               !empty($user->convive_con_mascotas);
    }
}