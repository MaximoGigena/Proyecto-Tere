<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Veterinario;

class VeterinariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $veterinarios = [
            [
                'nombre_completo' => 'Dra. Ana Martínez',
                'matricula' => 'VET-12345',
                'especialidad' => 'Cirugía',
                'foto' => null,
                'activo' => true,
                'estado' => Veterinario::ESTADO_APROBADO,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre_completo' => 'Dr. Roberto Sánchez',
                'matricula' => 'VET-67890',
                'especialidad' => 'Dermatología',
                'foto' => null,
                'activo' => true,
                'estado' => Veterinario::ESTADO_PENDIENTE, // Para probar estado pendiente
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre_completo' => 'Dra. Laura Fernández',
                'matricula' => 'VET-54321',
                'especialidad' => 'Oftalmología',
                'foto' => null,
                'activo' => true,
                'estado' => Veterinario::ESTADO_APROBADO,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($veterinarios as $veterinario) {
            Veterinario::create($veterinario);
        }
    }
}