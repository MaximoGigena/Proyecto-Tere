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
        Schema::create('farmacos', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de fármaco (obligatorio según vista)
            $table->foreignId('tipo_farmaco_id')
                  ->constrained('tipos_farmaco')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->dateTime('fecha_administracion');
            $table->string('frecuencia'); // Ej: Cada 8 h, 1 vez al día, etc.
            $table->string('duracion_tratamiento'); // Ej: 7 días, 2 semanas, etc.
            $table->string('dosis'); // Cantidad de la dosis (ej: "1", "2.5", etc.)
            $table->enum('unidad_dosis', ['mg', 'ml', 'UI', 'comp', 'gotas']);
            
            // Campos opcionales según la vista
            $table->dateTime('proxima_dosis')->nullable();
            $table->text('reacciones_adversas')->nullable();
            $table->text('recomendaciones_tutor')->nullable();
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_farmaco_id');
            $table->index('fecha_administracion');
            $table->index('unidad_dosis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmacos');
    }
};