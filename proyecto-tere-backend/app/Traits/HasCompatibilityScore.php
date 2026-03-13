<?php

namespace App\Traits;

use App\Models\Mascota;
use App\Services\MascotaMatchingService;

trait HasCompatibilityScore
{
    /**
     * Obtiene mascotas compatibles para el usuario
     */
    public function getCompatiblePets($limit = 20)
    {
        $matchingService = app(MascotaMatchingService::class);
        return $matchingService->getCompatiblePets($this, $limit);
    }

    /**
     * Calcula compatibilidad con una mascota específica
     */
    public function compatibilityWith(Mascota $mascota)
    {
        $matchingService = app(MascotaMatchingService::class);
        return $matchingService->calculateCompatibilityScore($this, $mascota);
    }
}