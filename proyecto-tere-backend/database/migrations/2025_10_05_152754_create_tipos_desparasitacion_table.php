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
        Schema::create('tipos_desparasitacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->json('parasitos'); // Array de parásitos tratados
            $table->string('otros_parasitos')->nullable();
            $table->enum('via_administracion', ['oral', 'topica', 'inyectable', 'otra']);
            $table->json('especies'); // Array de especies
            $table->decimal('edad_minima', 5, 2);
            $table->enum('edad_unidad', ['semanas', 'meses']);
            $table->integer('frecuencia');
            $table->enum('frecuencia_unidad', ['dias', 'semanas', 'meses']);
            $table->text('recomendaciones')->nullable();
            $table->text('riesgos')->nullable();
            $table->string('dosis_recomendada')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('veterinario_id')
                  ->nullable()
                  ->constrained('veterinarios')
                  ->onDelete('cascade');
            
            // Índices para mejorar el rendimiento
            $table->index(['via_administracion']);
            $table->index(['edad_minima', 'edad_unidad']);
            $table->index(['activo']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_desparasitacion');
    }
};