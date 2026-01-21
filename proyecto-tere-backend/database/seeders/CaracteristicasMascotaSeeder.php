<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CaracteristicasMascota;
use App\Models\Mascota;

class CaracteristicasMascotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las mascotas
        $mascotas = Mascota::all();
        
        if ($mascotas->isEmpty()) {
            $this->command->info('No hay mascotas disponibles. Primero ejecuta MascotasSeeder.');
            return;
        }

        // Datos de características para cada tipo de mascota
        // VALORES CORREGIDOS según la migración
        $caracteristicasData = [
            // Características para perros
            'canino' => [
                'tamano' => ['pequeño', 'mediano', 'grande'],
                'pelaje' => ['corto', 'medio', 'largo'],
                'alimentacion' => ['Alimentación comercial', 'Dieta natural', 'Dieta mixta', 'Dietas especiales'],
                'energia' => ['Bajo', 'Medio', 'Alto'],
                'ejercicio' => ['Diariamente', 'Varias veces por semana', 'Semanalmente'],
                'comportamiento_animales' => ['Social', 'Territorial', 'Depredador', 'Temeroso', 'Agresivo', 'Indeterminado'],
                'comportamiento_ninos' => ['Paciente', 'Juguetón', 'Temeroso', 'Estresado', 'Agresivo', 'Indeterminado'],
                'personalidad' => ['Amigable', 'Curioso', 'Tranquilo', 'Protector', 'Reservado', 'Nervioso', 'Territorial'],
            ],
            
            // Características para gatos
            'felino' => [
                'tamano' => ['pequeño', 'mediano'],
                'pelaje' => ['corto', 'medio', 'largo'],
                'alimentacion' => ['Alimentación comercial', 'Dieta natural', 'Dieta mixta'],
                'energia' => ['Bajo', 'Medio', 'Alto'],
                'ejercicio' => ['Varias veces por semana', 'Semanalmente', 'Ocasionalmente'],
                'comportamiento_animales' => ['Territorial', 'Depredador', 'Temeroso', 'Social', 'Agresivo', 'Indeterminado'],
                // CORRECCIÓN: 'Reservado' no es válido para comportamiento_ninos
                'comportamiento_ninos' => ['Paciente', 'Juguetón', 'Temeroso', 'Estresado', 'Agresivo', 'Indeterminado'],
                'personalidad' => ['Reservado', 'Curioso', 'Nervioso', 'Territorial', 'Tranquilo', 'Amigable'],
            ],
            
            // Características para aves
            'ave' => [
                'tamano' => ['pequeño'],
                'pelaje' => null,
                'alimentacion' => ['Dieta natural', 'Dietas especiales'],
                'energia' => ['Bajo', 'Medio'],
                'ejercicio' => ['Ocasionalmente', 'No realiza ejercicio'],
                'comportamiento_animales' => ['Temeroso', 'Indeterminado'],
                'comportamiento_ninos' => ['Temeroso', 'Estresado', 'Indeterminado'],
                'personalidad' => ['Reservado', 'Nervioso', 'Curioso'],
            ],
            
            // Características para otras especies
            'equino' => [
                'tamano' => ['grande'],
                'pelaje' => ['corto', 'medio', 'largo'],
                'alimentacion' => ['Dieta natural', 'Dietas especiales'],
                'energia' => ['Medio', 'Alto'],
                'ejercicio' => ['Diariamente', 'Varias veces por semana'],
                'comportamiento_animales' => ['Social', 'Territorial', 'Temeroso'],
                'comportamiento_ninos' => ['Paciente', 'Juguetón', 'Temeroso'],
                'personalidad' => ['Amigable', 'Curioso', 'Tranquilo'],
            ],
            
            'bovino' => [
                'tamano' => ['grande'],
                'pelaje' => ['corto'],
                'alimentacion' => ['Dieta natural'],
                'energia' => ['Bajo', 'Medio'],
                'ejercicio' => ['Ocasionalmente'],
                'comportamiento_animales' => ['Social', 'Temeroso'],
                'comportamiento_ninos' => ['Paciente', 'Temeroso'],
                'personalidad' => ['Tranquilo', 'Reservado'],
            ],
            
            'pez' => [
                'tamano' => ['pequeño'],
                'pelaje' => null,
                'alimentacion' => ['Alimentación comercial', 'Dietas especiales'],
                'energia' => ['Bajo'],
                'ejercicio' => ['No realiza ejercicio'],
                'comportamiento_animales' => ['Indeterminado'],
                'comportamiento_ninos' => ['Indeterminado'],
                'personalidad' => ['Reservado'],
            ],
            
            // Características para otras especies
            'otro' => [
                'tamano' => ['pequeño'],
                'pelaje' => null,
                'alimentacion' => ['Dieta natural', 'Dietas especiales'],
                'energia' => ['Bajo', 'Medio'],
                'ejercicio' => ['Ocasionalmente', 'No realiza ejercicio'],
                'comportamiento_animales' => ['Temeroso', 'Indeterminado'],
                'comportamiento_ninos' => ['Temeroso', 'Estresado', 'Indeterminado'],
                'personalidad' => ['Reservado', 'Nervioso'],
            ],
        ];

        foreach ($mascotas as $mascota) {
            // Verificar si ya tiene características
            if (!$mascota->caracteristicas) {
                $especie = $mascota->especie;
                $caracteristicasPorEspecie = $caracteristicasData[$especie] ?? $caracteristicasData['otro'];
                
                // Generar descripción personalizada
                $descripcion = $this->generarDescripcion($mascota);
                
                // Preparar datos asegurando que sean válidos
                $datosCaracteristicas = [
                    'mascota_id' => $mascota->id,
                    'tamano' => $caracteristicasPorEspecie['tamano'] 
                        ? $caracteristicasPorEspecie['tamano'][array_rand($caracteristicasPorEspecie['tamano'])]
                        : null,
                    'pelaje' => $caracteristicasPorEspecie['pelaje'] && in_array($especie, ['canino', 'felino', 'equino', 'bovino'])
                        ? $caracteristicasPorEspecie['pelaje'][array_rand($caracteristicasPorEspecie['pelaje'])]
                        : null,
                    'alimentacion' => $caracteristicasPorEspecie['alimentacion'][array_rand($caracteristicasPorEspecie['alimentacion'])],
                    'energia' => $caracteristicasPorEspecie['energia'][array_rand($caracteristicasPorEspecie['energia'])],
                    'ejercicio' => $caracteristicasPorEspecie['ejercicio'][array_rand($caracteristicasPorEspecie['ejercicio'])],
                    'comportamiento_animales' => $caracteristicasPorEspecie['comportamiento_animales'][array_rand($caracteristicasPorEspecie['comportamiento_animales'])],
                    'comportamiento_ninos' => $caracteristicasPorEspecie['comportamiento_ninos'][array_rand($caracteristicasPorEspecie['comportamiento_ninos'])],
                    'personalidad' => $caracteristicasPorEspecie['personalidad'][array_rand($caracteristicasPorEspecie['personalidad'])],
                    'descripcion' => $descripcion,
                ];

                // Crear las características
                CaracteristicasMascota::create($datosCaracteristicas);
            }
        }

        $this->command->info('CaracteristicasMascotaSeeder completado. Se crearon características para ' . $mascotas->count() . ' mascotas.');
    }

    /**
     * Generar una descripción personalizada para la mascota
     */
    private function generarDescripcion($mascota): string
    {
        $descriptores = [
            'canino' => [
                'es un compañero fiel y leal',
                'le encanta jugar y correr',
                'es muy cariñoso con su familia',
                'es protector y vigilante',
                'tiene mucha energía para jugar',
                'es tranquilo y relajado',
            ],
            'felino' => [
                'es independiente pero cariñoso',
                'le encanta dormir en lugares cálidos',
                'es curioso y le gusta explorar',
                'es juguetón y activo',
                'es tranquilo y reservado',
                'le gusta recibir mimos en sus términos',
            ],
            'ave' => [
                'es una mascota colorida y alegre',
                'tiene un canto muy melodioso',
                'es inteligente y curioso',
                'necesita mucha atención y cuidados',
            ],
            'equino' => [
                'es un animal noble y fuerte',
                'necesita mucho espacio para ejercitarse',
                'es muy inteligente y sensible',
                'crea un vínculo especial con su dueño',
            ],
            'bovino' => [
                'es un animal tranquilo y dócil',
                'necesita grandes espacios abiertos',
                'es social y vive en manada',
            ],
            'pez' => [
                'es una mascota tranquila y decorativa',
                'necesita un acuario bien equipado',
                'es fascinante observar sus movimientos',
            ],
            'otro' => [
                'es una mascota especial y única',
                'requiere cuidados específicos',
                'es tranquilo y fácil de cuidar',
                'le gusta su espacio personal',
            ],
        ];

        $especie = $mascota->especie;
        $descriptor = $descriptores[$especie] ?? $descriptores['otro'];
        
        $descripcion = ucfirst($mascota->nombre) . ' ' . 
               $descriptor[array_rand($descriptor)] . '. ';
        
        // Solo agregar información de castrado si aplica (no para todas las especies)
        if (in_array($especie, ['canino', 'felino'])) {
            $descripcion .= 'Es ' . ($mascota->sexo === 'macho' ? 'un macho' : 'una hembra') . ' ';
            
            if ($mascota->castrado !== null) {
                $descripcion .= $mascota->castrado ? 'castrado' : 'no castrado';
            }
        } else {
            $descripcion .= 'Es ' . ($mascota->sexo === 'macho' ? 'un macho' : 'una hembra') . '.';
        }
        
        return $descripcion;
    }
}