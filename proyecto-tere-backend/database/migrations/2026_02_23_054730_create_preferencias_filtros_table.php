<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('preferencias_filtros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Filtros de mascotas
            $table->json('especies')->nullable(); // ['caninos', 'felinos', ...]
            $table->json('sexos')->nullable();    // ['macho', 'hembra']
            $table->json('rangos_edad')->nullable(); // ['cachorro', 'joven', ...]
            
            // Filtros de ubicación
            $table->string('ubicacion_nombre')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->integer('radio_km')->default(10);
            
            // Metadatos
            $table->string('nombre_filtro')->default('Filtros preferenciales');
            $table->boolean('es_activo')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index('user_id');
            $table->index('es_activo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('preferencias_filtros');
    }
};