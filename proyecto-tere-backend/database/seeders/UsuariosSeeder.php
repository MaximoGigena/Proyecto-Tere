<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('usuarios')->insert([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password123'),
            'telefono' => '123456789',
            'edad' => 30,
            'activo' => true,
            'latitud' => -34.6037,
            'longitud' => -58.3816,
            'foto_perfil' => null,
        ]);
    }
}
