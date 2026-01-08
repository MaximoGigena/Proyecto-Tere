<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Administrador;

class AdministradoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administradores = [
            [
                'nombre_completo' => 'Admin Principal',
                'nivel_acceso' => 'superadmin',
                'ultimo_login' => now(),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre_completo' => 'Admin Soporte',
                'nivel_acceso' => 'soporte',
                'ultimo_login' => null,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($administradores as $admin) {
            Administrador::create($admin);
        }
    }
}