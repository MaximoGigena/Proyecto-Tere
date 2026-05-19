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
        // Obtener todas las mascotas
        $mascotas = Mascota::with('fotos')->get();
        
        if ($mascotas->isEmpty()) {
            $this->command->info('No hay mascotas disponibles. Primero ejecuta MascotasSeeder.');
            return;
        }

        // Banco de imágenes organizado por especie y NOMBRE de mascota
        // (coincide con los nombres generados por NombresMascotasHelper)
        $bancoImagenes = [
            'canino' => [
                // Nombres comunes de perros (los que genera tu helper)
                'max' => [
                    'mascotas/22/lIjOnKfw2J3h7a2aAyMbDEygtxPFqgOBlldN0HQd.webp',
                    'mascotas/22/LVgiAPkevCiiXD9oXym8ZEfBsdMPZHzu0Zq69u4U.webp',
                    'mascotas/22/Mbn6HdiCEA8XpzpsS5pG3NalXBjQKqtP6bw4ceur.webp',
                ],
                'luna' => [
                    'mascotas/4/5UnHqC7vc2GQe84LIKy8WfJOCxlXIQaxX8odp0ii.webp',
                    'mascotas/4/BQLmVuI9Wye1p6fc82akhhtSwLihBf2v9PAnkT1a.webp',
                ],
                'rocky' => [
                    'mascotas/23/jfoDSSRzbPrIBWDGa5pbR2xfDGwO5GYM3JYGHZmY.webp',
                    'mascotas/23/NB4shdAgTuP5ztHSQ1PQeQpuespCMdsto6aMeJFI.webp',
                    'mascotas/23/dKBCKX4F8pucqMA9djBpeOLyaActVOKYQAHwC527.webp',
                    'mascotas/22/7mccsVyOtGnm4meYGuCWjct6X6aw75DPXIcDL19Y.webp',
                ],
                'bella' => [
                    'mascotas/21/H5rlGlGkVj4zbmrer84ISNZevbBw82CVMVit9Eds.webp',
                    'mascotas/21/kvy6r8E6WaKUdWQRNJRtm6slleIAc2gdIGiMCqcK.webp',
                ],
                'toby' => [
                    'mascotas/2/pRvSZw1w6My1pDUyQ5cCzE0fxA4BmXSIXMCy6xqX.webp',
                    'mascotas/2/syz6QVD8wjWszFixUC7tJwsdx0jl1owr9hcFUiDQ.webp',
                    'mascotas/2/VmZwNzNPhWNcm4ZJBDDGtx1kONvrBunFzlZrA6VH.webp',
                ],
                // Agrega más nombres según lo que genere tu helper
                'default' => [ // Fallback para perros sin imágenes específicas
                    'mascotas/2/TLyvdnZzbTVS4sl42z3vMIkdfjAbkONP5bEuqhyK.webp',
                    'mascotas/22/s56ed9rPujAE5l7u6XwlRK4csYLuwRVcQm9YYxBZ.webp',
                    'mascotas/24/Ahoj0htCCwYzhhgCUBugpiQDDH4eyPz6DfrpJrjW.webp',
                ]
            ],
            'felino' => [
                'mishi' => [
                    'mascotas/3/tCsBUwacBkMsqJgjkQwvlhWoSa4byIG7lolWOr6k.webp',
                    'mascotas/3/QZNokApdmcjMuVcAe5AwhXdRVQDivYi454eOEGAv.webp',
                ],
                'simba' => [
                    'mascotas/4/qLRpB8G2khGJdlyxCLXc488pRs1eNjsi00SLUXXi.webp',
                    'mascotas/4/oGuzlAmfN3rxTavOnG7BHN2aAcMCsETuzgPZzFaN.webp',
                    'mascotas/4/kxvNGrWD6DhFbIdV9iXAJ1zwlhgxZl8M0C3fAHgZ.webp',
                ],
                'luna' => [
                    'mascotas/21/SYqvmle9kHPkVx5fpXxoBZdcFfEDz8th0WAcs3Xo.webp',
                    'mascotas/21/00ICT6QUrf1sHipFrB5M5QS2QWHfMW4G4I6kdCoA.webp',
                    'mascotas/21/SYqvmle9kHPkVx5fpXxoBZdcFfEDz8th0WAcs3Xo.webp',
                ],
                'felix' => [
                    'mascotas/23/TfzbNKPI0EJjNchWTbrrlt5rHoF9dAQUmlTEJtw8.webp',
                    'mascotas/23/wn9x6n8a6Wc0JX6uLZZE9CZklhqdBiqf6yC4Aj0D.webp',
                ],
                'default' => [ // Fallback para gatos sin imágenes específicas
                    'mascotas/3/tCsBUwacBkMsqJgjkQwvlhWoSa4byIG7lolWOr6k.webp',
                    'mascotas/3/QZNokApdmcjMuVcAe5AwhXdRVQDivYi454eOEGAv.webp',
                ]
            ],
            'ave' => [
                'piolin' => [
                    'mascotas/1/3gUDtMC3QDHoclOEjLReaAtjApyp73RJsnALTKoY.webp',
                    'mascotas/1/CjC4cIbn0uqgKHvuSFNRzp3L8BFVOlV9Xa6uShQ6.webp',
                ],
                'default' => [
                    'mascotas/1/3gUDtMC3QDHoclOEjLReaAtjApyp73RJsnALTKoY.webp',
                    'mascotas/1/CjC4cIbn0uqgKHvuSFNRzp3L8BFVOlV9Xa6uShQ6.webp',
                ]
            ],
            'otro' => [
                'default' => [
                    'mascotas/2/PPWKi8m8g1lRQ9u3xrljUmrArcALrUvMkX04cr0e.webp',
                    'mascotas/2/PU9UZDh06Jb5lqYdibludqeQL4JSy65ouTv53VV0.webp',
                ]
            ],
        ];

        $totalFotosCreadas = 0;
        $mascotasSinFotos = 0;
        
        foreach ($mascotas as $mascota) {
            // Solo procesar mascotas sin fotos
            if ($mascota->fotos->isNotEmpty()) {
                $this->command->info("ℹ️ {$mascota->nombre} ya tiene {$mascota->fotos->count()} foto(s)");
                continue;
            }
            
            // Normalizar el nombre para usarlo como clave
            $nombreKey = strtolower(trim($mascota->nombre));
            $especie = $mascota->especie;
            
            // Buscar imágenes específicas para esta mascota por su nombre
            $fotosDisponibles = $this->getFotosPorNombreYEspecie(
                $bancoImagenes, 
                $especie, 
                $nombreKey
            );
            
            if (empty($fotosDisponibles)) {
                $this->command->warn("⚠️ No hay fotos asignadas para '{$mascota->nombre}' ({$especie})");
                $mascotasSinFotos++;
                continue;
            }
            
            // Determinar cuántas fotos asignar (entre 1 y 4, o todas si son menos)
            $numFotos = min(rand(2, 4), count($fotosDisponibles));
            
            // Mezclar para variar el orden
            shuffle($fotosDisponibles);
            
            // Crear los registros de fotos
            for ($i = 0; $i < $numFotos; $i++) {
                $rutaFoto = $fotosDisponibles[$i];
                $rutaCompleta = storage_path('app/public/' . $rutaFoto);
                
                // Verificar existencia física de la imagen
                if (!file_exists($rutaCompleta)) {
                    $this->command->warn("⚠️ Imagen no encontrada: {$rutaFoto}");
                    continue;
                }
                
                MascotaFoto::create([
                    'mascota_id' => $mascota->id,
                    'ruta_foto' => $rutaFoto,
                    'es_principal' => $i === 0, // La primera es la principal
                ]);
                
                $totalFotosCreadas++;
            }
            
            $this->command->info("✅ {$mascota->nombre} ({$especie}): {$numFotos} foto(s) asignada(s)");
        }

        // Resumen final
        $this->command->newLine();
        $this->command->info("═══════════════════════════════════════════");
        $this->command->info("📸 MASCOTAFOTOSSEEDER - COMPLETADO");
        $this->command->info("═══════════════════════════════════════════");
        $this->command->info("Total mascotas procesadas: {$mascotas->count()}");
        $this->command->info("Total fotos creadas: {$totalFotosCreadas}");
        
        if ($mascotasSinFotos > 0) {
            $this->command->warn("⚠️ Mascotas sin fotos: {$mascotasSinFotos}");
            $this->command->info("💡 Sugerencia: Agrega más nombres al banco de imágenes");
        }
    }
    
    /**
     * Obtiene las fotos asignadas a una mascota específica por su nombre
     */
    private function getFotosPorNombreYEspecie(array $bancoImagenes, string $especie, string $nombre): array
    {
        // Verificar si existe la especie
        if (!isset($bancoImagenes[$especie])) {
            return $bancoImagenes['otro']['default'] ?? [];
        }
        
        // Buscar por nombre exacto
        if (isset($bancoImagenes[$especie][$nombre])) {
            return $bancoImagenes[$especie][$nombre];
        }
        
        // Buscar el nombre en minúsculas sin espacios (por si acaso)
        $nombreLimpio = str_replace(' ', '_', $nombre);
        if (isset($bancoImagenes[$especie][$nombreLimpio])) {
            return $bancoImagenes[$especie][$nombreLimpio];
        }
        
        // Fallback: imágenes por defecto de la especie
        if (isset($bancoImagenes[$especie]['default'])) {
            return $bancoImagenes[$especie]['default'];
        }
        
        // Último recurso: usar 'otro'
        return $bancoImagenes['otro']['default'] ?? [];
    }
}