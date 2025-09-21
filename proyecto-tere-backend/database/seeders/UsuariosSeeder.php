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
            'email' => 'jueean@example.com',
            'password' => Hash::make('password123'),
            'telefono' => '123436789',
            'edad' => 30,
            'activo' => true,
            'foto_perfil' => null,
        ]);

        DB::table('usuarios')->insert([
            'nombre' => 'Maxi pp',
            'email' => 'juant@exampdfvdfle.com',
            'password' => Hash::make('paedssword123'),
            'telefono' => '1234536789',
            'edad' => 30,
            'activo' => true,
            'foto_perfil' => null,
        ]);

        DB::table('usuarios')->insert([
            'nombre' => 'Tu señora madre',
            'email' => 'juano@exafvfmple.com',
            'password' => Hash::make('passdsword123'),
            'telefono' => '123456789',
            'edad' => 30,
            'activo' => true,
            'foto_perfil' => null,
        ]);
    }
}
