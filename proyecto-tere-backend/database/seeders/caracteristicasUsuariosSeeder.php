<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CaracteristicasUsuario;
use App\Models\Usuario;

class CaracteristicasUsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener los usuarios existentes
        $usuarios = Usuario::all();
        
        // Si no hay usuarios, crear algunos
        if ($usuarios->isEmpty()) {
            $this->call([UsuariosSeeder::class]);
            $usuarios = Usuario::all();
        }
        
        // Datos para características
        $caracteristicas = [
            [
                'tipoVivienda' => 'Casa',
                'ocupacion' => 'Desarrollador',
                'experiencia' => '2 años con mascotas',
                'convivenciaNiños' => 'si',
                'convivenciaMascotas' => 'si',
                'descripcion' => 'Vivo en una casa con patio grande, ideal para mascotas.',
                'usuario_id' => $usuarios[0]->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipoVivienda' => 'Departamento',
                'ocupacion' => 'Diseñador',
                'experiencia' => '5 años con perros',
                'convivenciaNiños' => 'no',
                'convivenciaMascotas' => 'si',
                'descripcion' => 'Departamento espacioso cerca de parques para pasear.',
                'usuario_id' => $usuarios[1]->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipoVivienda' => 'Casa con jardín',
                'ocupacion' => 'Profesor',
                'experiencia' => '10 años con gatos',
                'convivenciaNiños' => 'si',
                'convivenciaMascotas' => 'si',
                'descripcion' => 'Amplio jardín para que las mascotas jueguen libremente.',
                'usuario_id' => $usuarios[2]->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($caracteristicas as $caracteristica) {
            CaracteristicasUsuario::create($caracteristica);
        }
    }
}