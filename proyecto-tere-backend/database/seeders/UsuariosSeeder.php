<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuarios DIRECTAMENTE en la tabla users
        $usuarios = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan@example.com',
                'password' => Hash::make('password123'),
                'userable_type' => \App\Models\Usuario::class,
                'userable_id' => null, // Se asignará después
                'estado' => 'activo',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'María García',
                'email' => 'maria@example.com',
                'password' => Hash::make('password123'),
                'userable_type' => \App\Models\Usuario::class,
                'userable_id' => null,
                'estado' => 'activo',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Carlos López',
                'email' => 'carlos@example.com',
                'password' => Hash::make('password123'),
                'userable_type' => \App\Models\Usuario::class,
                'userable_id' => null,
                'estado' => 'activo',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($usuarios as $usuarioData) {
            // Primero crear el registro en la tabla usuarios
            $usuario = \App\Models\Usuario::create([
                'nombre' => $usuarioData['name'],
                'edad' => rand(20, 50),
                'foto_perfil' => null,
                'activo' => true,
            ]);

            // Luego crear el User con el userable_id correcto
            $usuarioData['userable_id'] = $usuario->id;
            User::create($usuarioData);
        }
    }
}