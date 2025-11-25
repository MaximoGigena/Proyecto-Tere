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
        Schema::create('tipos_alergia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion');
            $table->enum('categoria', [
                'medicamento',
                'alimento', 
                'ambiental', 
                'contacto', 
                'otra'
            ]);
            $table->string('categoria_otro')->nullable();
            $table->string('reaccion_comun');
            $table->enum('nivel_riesgo', [
                'leve',
                'moderado',
                'grave', 
                'muy_grave'
            ]);
            $table->json('areas_afectadas'); // Array de áreas afectadas
            $table->string('otra_area')->nullable();
            $table->text('tratamiento_recomendado')->nullable();
            $table->text('recomendaciones_clinicas')->nullable();
            $table->json('especies'); // Array de especies
            $table->string('desencadenante')->nullable();
            $table->text('conducta_recomendada')->nullable();
            $table->text('observaciones_adicionales')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['categoria']);
            $table->index(['nivel_riesgo']);
            $table->index(['activo']);

            $table->foreignId('veterinario_id')
                  ->nullable()
                  ->constrained('veterinarios')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_alergia');
    }
};
