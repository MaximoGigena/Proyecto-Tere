<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MotivoBaja;

class MotivosBajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $motivos = [
            ['descripcion' => 'Fallecimiento de la mascota'],
            ['descripcion' => 'Extraviada'],
            ['descripcion' => 'Adoptada por otra persona'],
            ['descripcion' => 'Traslado de domicilio'],
            ['descripcion' => 'Problemas de convivencia'],
            ['descripcion' => 'Problemas de salud'],
            ['descripcion' => 'Cambio de situación familiar'],
            ['descripcion' => 'Otra razón'],
        ];

        foreach ($motivos as $motivo) {
            MotivoBaja::create($motivo);
        }
    }
}