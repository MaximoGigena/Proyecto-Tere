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
        Schema::create('terapias', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de terapia (obligatorio según vista)
            $table->foreignId('tipo_terapia_id')
                  ->constrained('tipos_terapia')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->date('fecha_inicio');
            $table->enum('frecuencia', ['diaria', 'semanal', 'quincenal', 'mensual', 'personalizada']);
            $table->string('duracion_tratamiento'); // Ej: 3 meses, 10 sesiones, etc.
            
            // Campos opcionales según la vista
            $table->date('fecha_fin')->nullable();
            $table->enum('evolucion', ['mejoria', 'estable', 'empeoramiento'])->nullable();
            $table->text('recomendaciones_tutor')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_terapia_id');
            $table->index('fecha_inicio');
            $table->index('frecuencia');
            $table->index('evolucion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terapias');
    }
};
