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
        Schema::create('tipos_diagnostico', function (Blueprint $table) {
            $table->id();
            
            // Datos obligatorios
            $table->string('nombre');
            $table->text('descripcion');
            $table->enum('clasificacion', [
                'infeccioso',
                'genetico', 
                'nutricional',
                'ambiental',
                'traumatico',
                'degenerativo',
                'neoplasico',
                'otro'
            ]);
            $table->string('clasificacion_otro')->nullable();
            $table->enum('especie', [
                'canino', 
                'felino', 
                'ave', 
                'roedor', 
                'exotico', 
                'todos', 
                'ninguna'
            ]);
            $table->enum('evolucion', [
                'aguda',
                'cronica', 
                'recurrente',
                'autolimitada',
                'progresiva'
            ]);
            $table->text('criterios_diagnosticos');
            
            // Datos opcionales
            $table->text('tratamiento_sugerido')->nullable();
            $table->text('riesgos_complicaciones')->nullable();
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
            $table->index('clasificacion');
            $table->index('especie');
            $table->index('evolucion');
            $table->unique('nombre');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_diagnostico');
    }
};
