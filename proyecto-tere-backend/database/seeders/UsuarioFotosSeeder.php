<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UsuarioFoto;
use App\Models\Usuario;

class UsuarioFotosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los usuarios
        $usuarios = Usuario::all();
        
        // URLs de fotos de ejemplo (puedes usar estas o imágenes reales)
        $fotosEjemplo = [
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTTlKTQYBUDeQNnmkSUcouNCWLXHCpSy2qjvQ&s',
            'https://img.freepik.com/free-photo/lifestyle-beauty-fashion-people-emotions-concept-young-asian-female-office-manager-ceo-with-pleased-expression-standing-white-background-smiling-with-arms-crossed-chest_1258-59329.jpg?semt=ais_hybrid&w=740&q=80',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQ0d44tqByhnZu6tq1bUFozhJL1LhVnzkrkw&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQDu-k_hZT0lFOLgdNDAFXCMcRJjhp8vBZqsw&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSCtwNZcX7mUG3222R0mK0oDRbDU2HGhm7ACw&s',
        ];
        
        foreach ($usuarios as $index => $usuario) {
            // Crear foto principal para cada usuario
            UsuarioFoto::create([
                'usuario_id' => $usuario->id,
                'ruta_foto' => $fotosEjemplo[$index % count($fotosEjemplo)],
                'es_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Algunos usuarios tendrán fotos adicionales
            if ($index % 2 == 0) { // Usuarios pares tienen fotos extra
                UsuarioFoto::create([
                    'usuario_id' => $usuario->id,
                    'ruta_foto' => $fotosEjemplo[($index + 1) % count($fotosEjemplo)],
                    'es_principal' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Usuario específico con múltiples fotos
        $usuarioEspecial = $usuarios->first();
        if ($usuarioEspecial) {
            UsuarioFoto::create([
                'usuario_id' => $usuarioEspecial->id,
                'ruta_foto' => $fotosEjemplo[2],
                'es_principal' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            UsuarioFoto::create([
                'usuario_id' => $usuarioEspecial->id,
                'ruta_foto' => $fotosEjemplo[3],
                'es_principal' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}