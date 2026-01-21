<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ejecutar en orden para mantener las relaciones
        $this->call([
            // 1. Primero crear todos los usuarios CON sus ubicaciones
            UsersSeeder::class, // Este ahora incluye usuarios y ubicaciones
            
            // 2. Eliminar UbicacionUsuariosSeeder (ya se hace en UsersSeeder)
            
            // 3. Puedes agregar más seeders después según dependencias
            MascotasSeeder::class,
            CaracteristicasMascotaSeeder::class,
            MascotaFotosSeeder::class,

            // 4. Seeder de ofertas de adopción - AHORA SÍ funcionará
            OfertasAdopcionSeeder::class,
        ]);
    }
}
