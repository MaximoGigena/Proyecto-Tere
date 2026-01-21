<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mascota;
use App\Models\Usuario;
use Carbon\Carbon;

class MascotasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los usuarios para asignarles mascotas
        $usuarios = Usuario::all();
        
        if ($usuarios->isEmpty()) {
            $this->command->info('No hay usuarios disponibles. Primero ejecuta UsuariosSeeder.');
            return;
        }

        // Datos de mascotas de ejemplo
        $mascotas = [
            // Mascotas para el primer usuario (Juan Pérez)
            [
                'nombre' => 'Firulais',
                'especie' => 'canino',
                'fecha_nacimiento' => '15/03/2020',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[0]->id,
            ],
            [
                'nombre' => 'Luna',
                'especie' => 'felino',
                'fecha_nacimiento' => '10/08/2021',
                'sexo' => 'hembra',
                'castrado' => true,
                'usuario_id' => $usuarios[0]->id,
            ],
            
            // Mascotas para el segundo usuario (María García)
            [
                'nombre' => 'Max',
                'especie' => 'canino',
                'fecha_nacimiento' => '05/12/2019',
                'sexo' => 'macho',
                'castrado' => false,
                'usuario_id' => $usuarios[1]->id,
            ],
            [
                'nombre' => 'Milo',
                'especie' => 'felino',
                'fecha_nacimiento' => '20/06/2022',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[1]->id,
            ],
            [
                'nombre' => 'Piolín',
                'especie' => 'ave',
                'fecha_nacimiento' => '01/01/2023',
                'sexo' => 'macho',
                'castrado' => null,
                'usuario_id' => $usuarios[1]->id,
            ],
            
            // Mascotas para el tercer usuario (Carlos López)
            [
                'nombre' => 'Nala',
                'especie' => 'felino',
                'fecha_nacimiento' => '15/04/2020',
                'sexo' => 'hembra',
                'castrado' => true,
                'usuario_id' => $usuarios[2]->id,
            ],
            [
                'nombre' => 'Rocky',
                'especie' => 'canino',
                'fecha_nacimiento' => '30/09/2018',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[2]->id,
            ],
            [
                'nombre' => 'Bugs',
                'especie' => 'otro',
                'fecha_nacimiento' => '01/03/2022',
                'sexo' => 'macho',
                'castrado' => null,
                'usuario_id' => $usuarios[2]->id,
            ],
        ];

        foreach ($mascotas as $mascota) {
            Mascota::firstOrCreate(
                [
                    'nombre' => $mascota['nombre'],
                    'usuario_id' => $mascota['usuario_id'],
                ],
                $mascota
            );
        }

        $this->command->info('MascotasSeeder completado. Se crearon ' . count($mascotas) . ' mascotas.');
    }
}