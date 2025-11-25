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
        Schema::create('alergias', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de alergia (obligatorio según vista)
            $table->foreignId('tipo_alergia_id')
                  ->constrained('tipos_alergia')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->date('fecha_deteccion'); // Fecha de detección/diagnóstico
            $table->enum('gravedad', [
                'leve', 
                'moderada', 
                'grave'
            ]);
            $table->string('reaccion_comun', 255); // Reacción común observada
            $table->enum('estado', [
                'activa',
                'superada', 
                'seguimiento'
            ]);
            
            // Campos opcionales según la vista
            $table->string('desencadenante', 255)->nullable(); // Sustancia/factor desencadenante
            $table->text('conducta_recomendada')->nullable(); // Conducta recomendada ante exposición
            $table->text('recomendaciones_tutor')->nullable(); // Recomendaciones para el tutor
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_alergia_id');
            $table->index('fecha_deteccion');
            $table->index('gravedad');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alergias');
    }
};