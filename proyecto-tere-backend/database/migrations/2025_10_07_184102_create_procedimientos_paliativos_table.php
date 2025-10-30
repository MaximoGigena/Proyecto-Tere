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
        Schema::create('tipos_paliativo', function (Blueprint $table) {
            $table->id();
            
            // Datos obligatorios
            $table->string('nombre');
            $table->text('descripcion');
            $table->enum('especie', ['canino', 'felino', 'ave', 'roedor', 'exotico', 'todos']);
            $table->enum('objetivo_terapeutico', [
                'alivio_dolor',
                'mejora_movilidad', 
                'soporte_respiratorio',
                'soporte_nutricional',
                'acompañamiento',
                'otro'
            ]);
            $table->string('objetivo_otro')->nullable();
            $table->integer('frecuencia_valor');
            $table->enum('frecuencia_unidad', ['horas', 'dias', 'semanas', 'meses', 'sesiones']);
            $table->text('indicaciones_clinicas');
            
            // Datos opcionales
            $table->text('contraindicaciones')->nullable();
            $table->text('riesgos_efectos_secundarios')->nullable();
            $table->json('recursos_necesarios')->nullable();
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
            $table->index('objetivo_terapeutico');
            $table->index('frecuencia_unidad');
            $table->unique('nombre');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_paliativo');
    }
};
