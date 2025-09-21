<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class caracteristicasUsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('caracteristicas_usuarios')->insert([
            'tipoVivienda' => 'Lunar',
            'ocupacion' => 'gigolo',
            'experiencia' => 'sodaEstero',
            'convivenciaNiños' => 'si',
            'convivenciaMascotas' => 'no',
            'usuario_id' => 1,
            'descripción' => 'Vivo en un lugar con muchas estrellas y me gusta tocar la guitarra.',]);

        DB::table('caracteristicas_usuarios')->insert([
            'tipoVivienda' => 'Casa rodante',
            'ocupacion' => 'domador de dragones',
            'experiencia' => 'muchas batallas en Skyrim',
            'convivenciaNiños' => 'no',
            'convivenciaMascotas' => 'sí',
            'usuario_id' => 2,
            'descripción' => 'Viajo por el mundo en mi casa rodante con mi perro dragón llamado Fuego.'
]);
        DB::table('caracteristicas_usuarios')->insert([
            'tipoVivienda' => 'Cueva',
            'ocupacion' => 'recolector de setas mágicas',
            'experiencia' => 'cazador de tesoros',
            'convivenciaNiños' => 'no',
            'convivenciaMascotas' => 'sí',
            'usuario_id' => 3,
            'descripción' => 'Vivo en una cueva mágica llena de setas que brillan en la oscuridad y me gusta explorar ruinas antiguas.'
        ]);

    }
}
