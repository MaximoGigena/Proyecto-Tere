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
        Schema::create('desparasitaciones', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de desparasitación (obligatorio según vista)
            $table->foreignId('tipo_desparasitacion_id')
                  ->constrained('tipos_desparasitacion')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->date('fecha'); // Fecha de la desparasitación
            $table->string('nombre_producto'); // Nombre del desparasitante
            $table->string('dosis'); // Dosis aplicada
            $table->integer('frecuencia_valor'); // Valor numérico de frecuencia
            $table->enum('frecuencia_unidad', ['dias', 'semanas', 'meses']); // Unidad de frecuencia
            
            // Campos opcionales según la vista
            $table->decimal('peso', 5, 2)->nullable(); // Peso de la mascota en kg
            $table->date('proxima_fecha')->nullable(); // Próxima fecha sugerida
            $table->text('observaciones')->nullable(); // Observaciones adicionales
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_desparasitacion_id');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desparasitaciones');
    }
};