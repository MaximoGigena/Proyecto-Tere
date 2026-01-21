<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MascotaFoto;
use App\Models\Mascota;

class MascotaFotosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las mascotas CON fotos cargadas
        $mascotas = Mascota::with('fotos')->get();
        
        if ($mascotas->isEmpty()) {
            $this->command->info('No hay mascotas disponibles. Primero ejecuta MascotasSeeder.');
            return;
        }

        // Rutas RELATIVAS dentro de storage/app/public - CORREGIDAS
        $fotosPorEspecie = [
            'canino' => [
                'mascotas/1/1jIOBtUXuPeaRxqkmNTIaT2uLVusDc1eu8zSa2ST.webp',
                'mascotas/1/2IybuBADh5TR9row3TeDzR6wzkxLEPN0BpYnl1Dg.webp',
                'mascotas/1/8BTFG8VeZTGk1SeeT3HXklJtW9qYliIPCxsaYrkq.webp',
                'mascotas/1/dspHOmAKaUjs9e9kqQ76sAJ0HuZs0ByjzDr7pQ0c.webp',
            ],
            'felino' => [
                'mascotas/1/Erai5L7cnjIEr0kTgpPWChPooGTfgJxJb9lppqLv.webp',
                'mascotas/1/KnnYia8omqWGcqklsublOTP7vVBTmllkBlzjwhor.webp',
                'mascotas/1/xhng7QfwVfAB6TDzgkG0VfieSeiqI38AmS0hdcXk.webp',
                'mascotas/5/fOM41BEp9sG6pMSGg2C95WCglyMQVcR6Wmt41uS8.webp',
            ],
            'ave' => [
                'mascotas/1/jQjba1nk38BeTLMknGjmBkLtgi4E4eqXdZdCqdnd.webp',
                'mascotas/1/CjC4cIbn0uqgKHvuSFNRzp3L8BFVOlV9Xa6uShQ6.webp',
            ],
            'otro' => [
                'mascotas/4/GGtuwkibOEp8nVwsTy8xgTQzoYZYINSq0B0OpyMs.webp',
                'mascotas/4/ucvVvDAJ5DOirqfGJ8B1MEMmiNvo44MOtCpkaAKo.webp',
            ],
        ];

        $totalFotosCreadas = 0;
        
        foreach ($mascotas as $mascota) {
            // Verificar si ya tiene fotos
            if ($mascota->fotos->isEmpty()) {
                $especie = $mascota->especie;
                $fotosDisponibles = $fotosPorEspecie[$especie] ?? $fotosPorEspecie['otro'];
                
                // Determinar cuántas fotos agregar (1-4 fotos por mascota)
                $numFotos = min(rand(1, 4), count($fotosDisponibles));
                
                // Mezclar las fotos disponibles para obtener diferentes combinaciones
                shuffle($fotosDisponibles);
                
                for ($i = 0; $i < $numFotos; $i++) {
                    $rutaFoto = $fotosDisponibles[$i];
                    
                    // Verificar que la imagen existe físicamente
                    $rutaCompleta = storage_path('app/public/' . $rutaFoto);
                    
                    if (!file_exists($rutaCompleta)) {
                        $this->command->warn("⚠️ La imagen no existe: {$rutaCompleta}");
                        continue; // Saltar esta imagen
                    }
                    
                    MascotaFoto::create([
                        'mascota_id' => $mascota->id,
                        'ruta_foto' => $rutaFoto,
                        'es_principal' => $i === 0, // La primera foto es la principal
                    ]);
                    
                    $totalFotosCreadas++;
                }
                
                $this->command->info("✅ Mascota {$mascota->nombre} ({$especie}): {$numFotos} foto(s) agregada(s)");
            } else {
                $this->command->info("ℹ️ Mascota {$mascota->nombre} ya tiene {$mascota->fotos->count()} foto(s)");
            }
        }

        $this->command->info('✅ MascotaFotosSeeder completado.');
        $this->command->info("Total de mascotas procesadas: {$mascotas->count()}");
        $this->command->info("Total de fotos creadas: {$totalFotosCreadas}");
    }
}