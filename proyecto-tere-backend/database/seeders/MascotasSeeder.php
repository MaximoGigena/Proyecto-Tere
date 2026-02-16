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

        // Datos de mascotas de ejemplo (18 mascotas en total)
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
            
            // Mascotas para el cuarto usuario (Ana Martínez) - 2 mascotas
            [
                'nombre' => 'Toby',
                'especie' => 'canino',
                'fecha_nacimiento' => '12/05/2020',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[3]->id,
            ],
            [
                'nombre' => 'Mishi',
                'especie' => 'felino',
                'fecha_nacimiento' => '08/11/2021',
                'sexo' => 'hembra',
                'castrado' => true,
                'usuario_id' => $usuarios[3]->id,
            ],
            
            // Mascotas para el quinto usuario (Luis Rodríguez) - 3 mascotas
            [
                'nombre' => 'Thor',
                'especie' => 'canino',
                'fecha_nacimiento' => '22/02/2019',
                'sexo' => 'macho',
                'castrado' => false,
                'usuario_id' => $usuarios[4]->id,
            ],
            [
                'nombre' => 'Simba',
                'especie' => 'felino',
                'fecha_nacimiento' => '30/07/2020',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[4]->id,
            ],
            [
                'nombre' => 'Coco',
                'especie' => 'ave',
                'fecha_nacimiento' => '15/12/2022',
                'sexo' => 'hembra',
                'castrado' => null,
                'usuario_id' => $usuarios[4]->id,
            ],
            
            // Mascotas para el sexto usuario (Sofía Fernández) - 3 mascotas
            [
                'nombre' => 'Lola',
                'especie' => 'canino',
                'fecha_nacimiento' => '10/04/2021',
                'sexo' => 'hembra',
                'castrado' => true,
                'usuario_id' => $usuarios[5]->id,
            ],
            [
                'nombre' => 'Tom',
                'especie' => 'felino',
                'fecha_nacimiento' => '25/09/2019',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[5]->id,
            ],
            [
                'nombre' => 'Rex',
                'especie' => 'felino',
                'fecha_nacimiento' => '03/03/2023',
                'sexo' => 'macho',
                'castrado' => null,
                'usuario_id' => $usuarios[5]->id,
            ],
            
            // Mascotas para el séptimo usuario (Diego Gómez) - 2 mascotas
            [
                'nombre' => 'Kira',
                'especie' => 'canino',
                'fecha_nacimiento' => '18/06/2020',
                'sexo' => 'hembra',
                'castrado' => true,
                'usuario_id' => $usuarios[6]->id,
            ],
            
            // Mascotas para el octavo usuario (Valeria Díaz) - 3 mascotas
            [
                'nombre' => 'Bruno',
                'especie' => 'canino',
                'fecha_nacimiento' => '14/01/2018',
                'sexo' => 'macho',
                'castrado' => true,
                'usuario_id' => $usuarios[7]->id,
            ],
            [
                'nombre' => 'Luna',
                'especie' => 'felino',
                'fecha_nacimiento' => '05/05/2021',
                'sexo' => 'hembra',
                'castrado' => true,
                'usuario_id' => $usuarios[7]->id,
            ],
            [
                'nombre' => 'Nemo',
                'especie' => 'pez',
                'fecha_nacimiento' => '20/10/2022',
                'sexo' => 'macho',
                'castrado' => null,
                'usuario_id' => $usuarios[7]->id,
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

        $totalMascotas = Mascota::count();
        $this->command->info("MascotasSeeder completado. Se crearon {$totalMascotas} mascotas.");
    }
}