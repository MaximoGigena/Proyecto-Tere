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
        Schema::create('tipos_terapia', function (Blueprint $table) {
            $table->id();
            
            // Datos obligatorios
            $table->string('nombre');
            $table->text('descripcion');
            $table->enum('especie', ['canino', 'felino', 'ave', 'roedor', 'exotico', 'todos']);
            $table->integer('duracion_valor');
            $table->enum('duracion_unidad', ['sesiones', 'dias', 'semanas', 'meses']);
            $table->enum('frecuencia', ['diaria', 'semanal', 'quincenal', 'mensual', 'personalizada']);
            $table->text('requisitos');
            $table->text('indicaciones_clinicas');
            
            // Datos opcionales
            $table->text('contraindicaciones')->nullable();
            $table->text('riesgos_efectos_secundarios')->nullable();
            $table->text('recomendaciones_clinicas')->nullable();
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
            $table->index('especie');
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
        Schema::dropIfExists('tipos_terapia');
    }
};
