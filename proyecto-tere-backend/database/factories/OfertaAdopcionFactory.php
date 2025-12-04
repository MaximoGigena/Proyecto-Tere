<?php

namespace Database\Factories;

use App\Models\OfertaAdopcion;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfertaAdopcionFactory extends Factory
{
    protected $model = OfertaAdopcion::class;

    public function definition(): array
    {
        return [
            'id_mascota' => \App\Models\Mascota::factory(),
            'id_usuario_responsable' => \App\Models\Usuario::factory(),
            'estado_oferta' => $this->faker->randomElement([
                'publicada',
                'pausada',
                'en_proceso',
                'cerrada',
                'cancelada'
            ]),
            'permiso_historial_medico' => $this->faker->boolean(70),
            'permiso_contacto_tutor' => $this->faker->boolean(80),
        ];
    }

    public function publicada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_oferta' => 'publicada',
        ]);
    }

    public function conPermisosCompletos(): static
    {
        return $this->state(fn (array $attributes) => [
            'permiso_historial_medico' => true,
            'permiso_contacto_tutor' => true,
        ]);
    }
}