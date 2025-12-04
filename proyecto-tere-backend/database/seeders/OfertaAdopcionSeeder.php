<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OfertaAdopcion;

class OfertaAdopcionSeeder extends Seeder
{
    public function run(): void
    {
        // Crear 50 ofertas de adopción
        OfertaAdopcion::factory()->count(50)->create();
        
        // Crear algunas ofertas específicas
        OfertaAdopcion::factory()->count(10)->publicada()->create();
        OfertaAdopcion::factory()->count(5)->conPermisosCompletos()->create();
        
        $this->command->info('✅ 50 ofertas de adopción creadas exitosamente.');
    }
}