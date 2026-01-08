<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios normales
        $usuarios = Usuario::all();
        
        foreach ($usuarios as $index => $usuario) {
            User::create([
                'name' => $usuario->nombre,
                'email' => "usuario{$index}@example.com",
                'password' => Hash::make('password123'),
                'userable_type' => Usuario::class,
                'userable_id' => $usuario->id,
                'estado' => 'activo',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear veterinarios
        $veterinarios = Veterinario::all();
        
        foreach ($veterinarios as $index => $veterinario) {
            User::create([
                'name' => $veterinario->nombre_completo,
                'email' => "veterinario{$index}@example.com",
                'password' => Hash::make('password123'),
                'userable_type' => Veterinario::class,
                'userable_id' => $veterinario->id,
                'estado' => $veterinario->estado === 'aprobado' ? 'activo' : 'pendiente',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear administradores
        $administradores = Administrador::all();
        
        foreach ($administradores as $index => $admin) {
            User::create([
                'name' => $admin->nombre_completo,
                'email' => "admin{$index}@example.com",
                'password' => Hash::make('password123'),
                'userable_type' => Administrador::class,
                'userable_id' => $admin->id,
                'estado' => 'activo',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear un super admin especial
        $superAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperAdmin123!'),
            'userable_type' => null,
            'userable_id' => null,
            'estado' => 'activo',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear usuario con login social (Google) de ejemplo
        $socialUser = User::create([
            'name' => 'Usuario Google',
            'email' => 'socialuser@gmail.com',
            'password' => null, // No necesita password
            'userable_type' => Usuario::class,
            'userable_id' => $usuarios->first()->id,
            'estado' => 'activo',
            'google_id' => '1234567890abcdef',
            'avatar' => 'https://lh3.googleusercontent.com/a/default-user',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}