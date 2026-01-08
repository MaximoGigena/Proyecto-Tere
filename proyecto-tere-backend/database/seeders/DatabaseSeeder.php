<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar en orden para mantener las relaciones
        $this->call([
            // 1. Primero los modelos base
            UsuariosSeeder::class,
            VeterinariosSeeder::class,
            AdministradoresSeeder::class,
            
            // 2. Luego los Users que dependen de ellos
            UsersSeeder::class,
            
            // 3. Después los datos relacionados con Usuarios
            CaracteristicasUsuariosSeeder::class,
            UsuarioFotosSeeder::class,
            UbicacionUsuariosSeeder::class,
            
            // 4. Puedes agregar más seeders después según dependencias
            // ContactoUsuariosSeeder::class,
            // etc...
        ]);
    }
}
