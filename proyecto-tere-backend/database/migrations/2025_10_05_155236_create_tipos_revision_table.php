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
        Schema::create('tipos_revision', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion');
            $table->enum('frecuencia_recomendada', [
                'anual', 
                'semestral', 
                'trimestral', 
                'mensual', 
                'post_procedimiento', 
                'personalizada'
            ]);
            $table->string('frecuencia_personalizada')->nullable();
            $table->json('areas_revisar'); // Array de áreas a revisar
            $table->string('otra_area')->nullable();
            $table->text('indicadores_clave')->nullable();
            $table->enum('especie_objetivo', [
                'canino',
                'felino', 
                'ave', 
                'roedor', 
                'exotico', 
                'todos'
            ])->default('todos');
            $table->decimal('edad_sugerida', 5, 2)->nullable();
            $table->enum('edad_unidad', ['semanas', 'meses', 'años'])->nullable();
            $table->text('recomendaciones_profesionales')->nullable();
            $table->text('riesgos_clinicos')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para mejorar el rendimiento
            $table->index(['frecuencia_recomendada']);
            $table->index(['especie_objetivo']);
            $table->index(['edad_sugerida', 'edad_unidad']);
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
        Schema::dropIfExists('tipos_revision');
    }
};
