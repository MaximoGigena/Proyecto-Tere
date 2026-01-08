<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UbicacionUsuario;
use App\Models\Usuario;

class UbicacionUsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los usuarios
        $usuarios = Usuario::all();
        
        // Ubicaciones de ejemplo en diferentes ciudades de Argentina
        $ubicaciones = [
            // Buenos Aires
            [
                'latitude' => -34.6037,
                'longitude' => -58.3816,
                'location' => 'Buenos Aires, Argentina',
            ],
            // Córdoba
            [
                'latitude' => -31.4201,
                'longitude' => -64.1888,
                'location' => 'Córdoba, Argentina',
            ],
            // Rosario
            [
                'latitude' => -32.9468,
                'longitude' => -60.6393,
                'location' => 'Rosario, Argentina',
            ],
            // Mendoza
            [
                'latitude' => -32.8895,
                'longitude' => -68.8458,
                'location' => 'Mendoza, Argentina',
            ],
            // La Plata
            [
                'latitude' => -34.9205,
                'longitude' => -57.9536,
                'location' => 'La Plata, Argentina',
            ],
        ];
        
        foreach ($usuarios as $index => $usuario) {
            // Cada usuario tiene al menos una ubicación
            $ubicacionIndex = $index % count($ubicaciones);
            
            UbicacionUsuario::create([
                'usuario_id' => $usuario->id,
                'latitude' => $ubicaciones[$ubicacionIndex]['latitude'],
                'longitude' => $ubicaciones[$ubicacionIndex]['longitude'],
                'location' => $ubicaciones[$ubicacionIndex]['location'],
                'location_updated_at' => now()->subDays(rand(0, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Algunos usuarios tienen historial de ubicaciones
            if ($index % 3 == 0) { // Cada tercer usuario
                // Ubicación anterior
                $otraUbicacionIndex = ($index + 1) % count($ubicaciones);
                
                UbicacionUsuario::create([
                    'usuario_id' => $usuario->id,
                    'latitude' => $ubicaciones[$otraUbicacionIndex]['latitude'],
                    'longitude' => $ubicaciones[$otraUbicacionIndex]['longitude'],
                    'location' => $ubicaciones[$otraUbicacionIndex]['location'],
                    'location_updated_at' => now()->subDays(rand(31, 90)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Usuario específico con múltiples ubicaciones
        $usuarioActivo = $usuarios->first();
        if ($usuarioActivo) {
            // Crear varias ubicaciones históricas
            $ciudades = [
                ['lat' => -34.6037, 'lng' => -58.3816, 'name' => 'Buenos Aires'],
                ['lat' => -34.9214, 'lng' => -57.9544, 'name' => 'La Plata'],
                ['lat' => -31.4167, 'lng' => -64.1833, 'name' => 'Córdoba'],
                ['lat' => -24.7859, 'lng' => -65.4117, 'name' => 'Salta'],
            ];
            
            $diasAtras = 0;
            foreach ($ciudades as $ciudad) {
                UbicacionUsuario::create([
                    'usuario_id' => $usuarioActivo->id,
                    'latitude' => $ciudad['lat'],
                    'longitude' => $ciudad['lng'],
                    'location' => $ciudad['name'] . ', Argentina',
                    'location_updated_at' => now()->subDays($diasAtras),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $diasAtras += 7; // Una semana entre cada ubicación
            }
        }
    }
}