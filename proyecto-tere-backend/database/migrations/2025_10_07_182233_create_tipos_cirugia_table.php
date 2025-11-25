<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipos_cirugia', function (Blueprint $table) {
            $table->id();
            
            // Datos obligatorios
            $table->string('nombre');
            $table->text('descripcion');
            $table->json('especies'); // Array de especies
            $table->enum('frecuencia', ['unica', 'potencial_repetible', 'multiple']);
            $table->integer('duracion');
            $table->enum('duracion_unidad', ['minutos', 'horas']);
            $table->text('riesgos');
            $table->text('recomendaciones_preoperatorias');
            
            // Datos opcionales
            $table->text('recomendaciones_postoperatorias')->nullable();
            $table->text('requerimientos_anestesia')->nullable();
            $table->json('equipamiento')->nullable();
            $table->text('observaciones')->nullable();

            $table->boolean('activo')->default(true);
            $table->softDeletes();
            
            // Timestamps
            $table->timestamps();

            $table->foreignId('veterinario_id')
                  ->nullable()
                  ->constrained('veterinarios')
                  ->onDelete('cascade');
            
            // Índices
            $table->index('frecuencia');
            $table->unique('nombre');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_cirugia');
    }
};
