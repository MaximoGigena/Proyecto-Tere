<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Veterinario;
use App\Models\Administrador;
use App\Models\UbicacionUsuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Ubicaciones predefinidas
            $ubicaciones = [
                [
                    'latitude' => -34.6037,
                    'longitude' => -58.3816,
                    'city' => 'Buenos Aires',
                    'state' => 'Buenos Aires',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 15.5,
                ],
                [
                    'latitude' => -31.4201,
                    'longitude' => -64.1888,
                    'city' => 'Córdoba',
                    'state' => 'Córdoba',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 25.0,
                ],
                [
                    'latitude' => -31.4241,
                    'longitude' => -64.4978,
                    'city' => 'Villa Carlos Paz',
                    'state' => 'Córdoba',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 10.2,
                ],
                [
                    'latitude' => -32.9468,
                    'longitude' => -60.6393,
                    'city' => 'Rosario',
                    'state' => 'Santa Fe',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 18.7,
                ],
                [
                    'latitude' => -26.8083,
                    'longitude' => -65.2176,
                    'city' => 'San Miguel de Tucumán',
                    'state' => 'Tucumán',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 22.1,
                ],
                [
                    'latitude' => -34.9205,
                    'longitude' => -57.9536,
                    'city' => 'La Plata',
                    'state' => 'Buenos Aires',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 14.8,
                ],
                [
                    'latitude' => -38.9516,
                    'longitude' => -68.0591,
                    'city' => 'Neuquén',
                    'state' => 'Neuquén',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 30.2,
                ],
                [
                    'latitude' => -31.6285,
                    'longitude' => -60.6925,
                    'city' => 'Paraná',
                    'state' => 'Entre Ríos',
                    'country' => 'Argentina',
                    'country_code' => 'AR',
                    'accuracy' => 19.5,
                ],
            ];
            
            // 1. Primero crear Super Admin
            $superAdminRecord = Administrador::firstOrCreate(
                [
                    'nombre_completo' => 'Super Administrador',
                    'nivel_acceso' => 'superadmin',
                ],
                [
                    'ultimo_login' => now(),
                    'activo' => true,
                    'user_type' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 2. Crear Usuarios normales CON sus ubicaciones
            $usuariosData = [
                [
                    'nombre' => 'Juan Pérez',
                    'email' => 'juan@example.com',
                    'ubicacion_index' => 0,
                ],
                [
                    'nombre' => 'María García',
                    'email' => 'maria@example.com',
                    'ubicacion_index' => 1,
                ],
                [
                    'nombre' => 'Carlos López',
                    'email' => 'carlos@example.com',
                    'ubicacion_index' => 2,
                ],
                [
                    'nombre' => 'Ana Martínez',
                    'email' => 'ana@example.com',
                    'ubicacion_index' => 3,
                ],
                [
                    'nombre' => 'Luis Rodríguez',
                    'email' => 'luis@example.com',
                    'ubicacion_index' => 4,
                ],
                [
                    'nombre' => 'Sofía Fernández',
                    'email' => 'sofia@example.com',
                    'ubicacion_index' => 5,
                ],
                [
                    'nombre' => 'Diego Gómez',
                    'email' => 'diego@example.com',
                    'ubicacion_index' => 6,
                ],
                [
                    'nombre' => 'Valeria Díaz',
                    'email' => 'valeria@example.com',
                    'ubicacion_index' => 7,
                ],
            ];

            foreach ($usuariosData as $index => $usuarioData) {
                // Crear Usuario primero
                $usuario = Usuario::firstOrCreate(
                    ['nombre' => $usuarioData['nombre']],
                    [
                        'edad' => rand(20, 50),
                        'foto_perfil' => null,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Crear User
                $user = User::firstOrCreate(
                    ['email' => $usuarioData['email']],
                    [
                        'name' => $usuarioData['nombre'],
                        'password' => Hash::make('password123'),
                        'userable_type' => Usuario::class,
                        'userable_id' => $usuario->id,
                        'estado' => 'activo',
                        'email_verified_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Crear Ubicación para este usuario
                $ubicacion = $ubicaciones[$usuarioData['ubicacion_index']];
                $point = "POINT({$ubicacion['longitude']} {$ubicacion['latitude']})";
                
                UbicacionUsuario::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'latitude' => $ubicacion['latitude'],
                        'longitude' => $ubicacion['longitude'],
                        'location' => DB::raw("ST_GeomFromText('$point', 4326)"),
                        'country' => $ubicacion['country'],
                        'country_code' => $ubicacion['country_code'],
                        'state' => $ubicacion['state'],
                        'city' => $ubicacion['city'],
                        'source' => 'gps',
                        'accuracy' => $ubicacion['accuracy'],
                        'location_updated_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
    

        // Crear veterinarios - solo si no existen
        $veterinarios = Veterinario::all();
        
        foreach ($veterinarios as $index => $veterinario) {
            $email = "veterinario{$index}@example.com";
            
            User::firstOrCreate(
                [
                    'email' => $email,
                ],
                [
                    'name' => $veterinario->nombre_completo,
                    'password' => Hash::make('password123'),
                    'userable_type' => Veterinario::class,
                    'userable_id' => $veterinario->id,
                    'estado' => $veterinario->estado === 'aprobado' ? 'activo' : 'pendiente',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Crear administradores - solo si no existen
        $administradores = Administrador::all();
        
        foreach ($administradores as $index => $admin) {
            // Usar email específico para el Super Admin
            if ($admin->nivel_acceso === 'superadmin') {
                $email = 'superadmin@example.com';
            } else {
                $email = "admin{$index}@example.com";
            }
            
            User::firstOrCreate(
                [
                    'email' => $email,
                ],
                [
                    'name' => $admin->nombre_completo,
                    'password' => Hash::make('SuperAdmin123!'),
                    'userable_type' => Administrador::class,
                    'userable_id' => $admin->id,
                    'estado' => 'activo',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

         // 5. Crear usuario con login social de ejemplo
            if (Usuario::exists()) {
                $primerUsuario = Usuario::first();
                User::firstOrCreate(
                    ['email' => 'socialuser@gmail.com'],
                    [
                        'name' => 'Usuario Google',
                        'password' => null,
                        'userable_type' => Usuario::class,
                        'userable_id' => $primerUsuario->id,
                        'estado' => 'activo',
                        'google_id' => '1234567890abcdef',
                        'avatar' => 'https://lh3.googleusercontent.com/a/default-user',
                        'email_verified_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        $this->command->info('✅ UsersSeeder completado con usuarios y ubicaciones.');
    }
}