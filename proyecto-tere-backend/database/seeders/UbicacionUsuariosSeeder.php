<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UbicacionUsuario;
use Illuminate\Support\Facades\DB;

class UbicacionUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todos los Users (sin importar el tipo)
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->info('No hay usuarios para asignar ubicaciones.');
            return;
        }
        
        // Ubicaciones de ejemplo
        $ubicaciones = [
            [
                'latitude' => -34.6037,
                'longitude' => -58.3816,
                'city' => 'Buenos Aires',
                'state' => 'Buenos Aires',
                'country' => 'Argentina',
                'country_code' => 'AR',
                'accuracy' => 15.5,
            ],
            [
                'latitude' => -31.4201,
                'longitude' => -64.1888,
                'city' => 'Córdoba',
                'state' => 'Córdoba',
                'country' => 'Argentina',
                'country_code' => 'AR',
                'accuracy' => 25.0,
            ],
            [
                'latitude' => -32.9468,
                'longitude' => -60.6393,
                'city' => 'Rosario',
                'state' => 'Santa Fe',
                'country' => 'Argentina',
                'country_code' => 'AR',
                'accuracy' => 10.2,
            ],
            [
                'latitude' => -31.4201,
                'longitude' => -64.4992,
                'city' => 'Villa Carlos Paz',
                'state' => 'Córdoba',
                'country' => 'Argentina',
                'country_code' => 'AR',
                'accuracy' => 18.7,
            ],
            [
                'latitude' => -34.9205,
                'longitude' => -57.9536,
                'city' => 'La Plata',
                'state' => 'Buenos Aires',
                'country' => 'Argentina',
                'country_code' => 'AR',
                'accuracy' => 30.5,
            ],
        ];
        
        // Crear ubicaciones para cada usuario
        foreach ($users as $index => $user) {
            $ubicacionIndex = $index % count($ubicaciones);
            $ubicacion = $ubicaciones[$ubicacionIndex];
            
            // Crear punto para PostGIS
            $point = "POINT({$ubicacion['longitude']} {$ubicacion['latitude']})";
            
            // Insertar usando user_id
            DB::table('user_locations')->insert([
                'user_id' => $user->id, // ← user_id, no usuario_id
                'latitude' => $ubicacion['latitude'],
                'longitude' => $ubicacion['longitude'],
                'location' => DB::raw("ST_GeomFromText('$point', 4326)"),
                'country' => $ubicacion['country'],
                'country_code' => $ubicacion['country_code'],
                'state' => $ubicacion['state'],
                'city' => $ubicacion['city'],
                'source' => 'gps',
                'accuracy' => $ubicacion['accuracy'],
                'location_updated_at' => now()->subDays(rand(0, 7)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Algunos usuarios tienen ubicaciones históricas (30%)
            if ($index % 3 == 0) {
                $numHistoricas = rand(1, 2);
                
                for ($i = 0; $i < $numHistoricas; $i++) {
                    $historicIndex = ($ubicacionIndex + $i + 1) % count($ubicaciones);
                    $ubicacionHistorica = $ubicaciones[$historicIndex];
                    
                    $pointHist = "POINT({$ubicacionHistorica['longitude']} {$ubicacionHistorica['latitude']})";
                    
                    DB::table('user_locations')->insert([
                        'user_id' => $user->id, // ← user_id
                        'latitude' => $ubicacionHistorica['latitude'],
                        'longitude' => $ubicacionHistorica['longitude'],
                        'location' => DB::raw("ST_GeomFromText('$pointHist', 4326)"),
                        'country' => $ubicacionHistorica['country'],
                        'country_code' => $ubicacionHistorica['country_code'],
                        'state' => $ubicacionHistorica['state'],
                        'city' => $ubicacionHistorica['city'],
                        'source' => 'network',
                        'accuracy' => rand(20, 100) + (rand(0, 9) / 10),
                        'location_updated_at' => now()->subDays(rand(30, 180)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        
        $totalUbicaciones = DB::table('user_locations')->count();
        $this->command->info("✅ UbicacionesSeeder completado. {$totalUbicaciones} ubicaciones creadas.");
    }
}