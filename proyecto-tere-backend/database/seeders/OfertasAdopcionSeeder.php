<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OfertaAdopcion;
use App\Models\Mascota;
use Carbon\Carbon;

class OfertasAdopcionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // En Database\Seeders\OfertasAdopcionSeeder
    public function run(): void
    {
        // Obtener todas las mascotas CON sus usuarios
        $mascotas = Mascota::with('usuario')->get();
        
        if ($mascotas->isEmpty()) {
            $this->command->info('No hay mascotas disponibles. Primero ejecuta MascotasSeeder.');
            return;
        }
        
        // Contador para estadísticas
        $totalOfertasCreadas = 0;

        foreach ($mascotas as $mascota) {
            // Verificar si ya tiene una oferta de adopción
            if (!$mascota->ofertasAdopcion()->exists()) {
                
                // Verificar que el usuario tenga ubicación
                if (!$mascota->usuario || !$mascota->usuario->user) {
                    $this->command->warn("Mascota {$mascota->nombre} no tiene usuario válido, omitiendo.");
                    continue;
                }
                
                $user = $mascota->usuario->user;
                if (!$user || !$user->ubicacionActual) {
                    $this->command->warn("Usuario de la mascota {$mascota->nombre} no tiene ubicación, omitiendo.");
                    continue;
                }
                
                // Determinar permisos basados en el tipo de mascota
                $permisos = $this->determinarPermisos($mascota);
                
                // Crear la oferta de adopción
                OfertaAdopcion::create([
                    'id_mascota' => $mascota->id,
                    'id_usuario_responsable' => $mascota->usuario_id,
                    'estado_oferta' => 'publicada',
                    'permiso_historial_medico' => $permisos['historial_medico'],
                    'permiso_contacto_tutor' => $permisos['contacto_tutor'],
                    'created_at' => $this->generarFechaCreacionAleatoria(),
                    'updated_at' => now(),
                ]);
                
                $totalOfertasCreadas++;
                $this->command->info("✅ Oferta creada para {$mascota->nombre}");
            }
        }

        $this->command->info('OfertasAdopcionSeeder completado.');
        $this->command->info("Total de ofertas creadas: {$totalOfertasCreadas}");
        $this->command->info("Total de mascotas procesadas: {$mascotas->count()}");
    }

    /**
     * Determinar permisos basados en el tipo de mascota
     */
    private function determinarPermisos($mascota): array
    {
        $permisos = [
            'historial_medico' => false,
            'contacto_tutor' => false,
        ];
        
        // Para perros y gatos, más probabilidades de tener permisos
        if (in_array($mascota->especie, ['canino', 'felino'])) {
            // Historial médico: alta probabilidad (80%)
            $permisos['historial_medico'] = rand(1, 10) <= 8;
            
            // Contacto tutor: casi siempre permitido (90%)
            $permisos['contacto_tutor'] = rand(1, 10) <= 9;
        } 
        // Para otras especies
        else {
            // Historial médico: probabilidad media (60%)
            $permisos['historial_medico'] = rand(1, 10) <= 6;
            
            // Contacto tutor: alta probabilidad (85%)
            $permisos['contacto_tutor'] = rand(1, 10) <= 8.5;
        }
        
        return $permisos;
    }

    /**
     * Generar fecha de creación aleatoria (últimos 90 días)
     */
    private function generarFechaCreacionAleatoria(): string
    {
        return Carbon::now()
            ->subDays(rand(1, 90)) // Entre 1 y 90 días atrás
            ->subHours(rand(1, 23)) // Hora aleatoria
            ->subMinutes(rand(1, 59)) // Minuto aleatorio
            ->toDateTimeString();
    }

    /**
     * Crear una oferta histórica cerrada
     * Esto crea un historial de ofertas anteriores para algunas mascotas
     */
    private function crearOfertaHistorica($mascota): void
    {
        // Determinar si fue cerrada exitosamente
        $exitosa = rand(1, 10) <= 7; // 70% de éxito
        
        // Verificar que el usuario tenga ubicación
        $usuario = $mascota->usuario;
        if (!$usuario || !$usuario->ubicacionActual) {
            $this->command->warn("Mascota {$mascota->nombre} no tiene usuario con ubicación, omitiendo oferta histórica.");
            return;
        }
        
        // Crear fecha más antigua que la oferta actual
        $fechaCreacion = Carbon::now()
            ->subDays(rand(120, 365)) // Hace 4 a 12 meses
            ->toDateTimeString();
            
        $fechaActualizacion = Carbon::parse($fechaCreacion)
            ->addDays(rand(30, 90)) // Actualizada 1-3 meses después
            ->toDateTimeString();
        
        OfertaAdopcion::create([
            'id_mascota' => $mascota->id,
            'id_usuario_responsable' => $mascota->usuario_id,
            'estado_oferta' => 'cerrada',
            'permiso_historial_medico' => $exitosa ? rand(1, 10) <= 8 : rand(1, 10) <= 2,
            'permiso_contacto_tutor' => $exitosa ? rand(1, 10) <= 9 : rand(1, 10) <= 3,
            'created_at' => $fechaCreacion,
            'updated_at' => $fechaActualizacion,
            'deleted_at' => null,
        ]);
    }

    
}