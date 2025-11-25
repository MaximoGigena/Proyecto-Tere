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
        Schema::create('cuidados_paliativos', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de procedimiento paliativo (obligatorio según vista)
            $table->foreignId('tipo_paliativo_id')
                  ->constrained('tipos_paliativo')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->dateTime('fecha_inicio');
            $table->string('diagnostico_base'); // Motivo que justificó los cuidados
            $table->enum('resultado', ['mejoria', 'alivio', 'estabilizacion', 'sin_cambio', 'empeoramiento']);
            $table->enum('estado_mascota', ['estable', 'dolor_controlado', 'dolor_parcial', 'deterioro', 'critico']);
            
            // Campos opcionales según la vista
            $table->integer('frecuencia_valor')->nullable(); // Cantidad numérica
            $table->enum('frecuencia_unidad', ['horas', 'dias', 'semanas', 'meses'])->nullable();
            $table->text('medicacion_complementaria')->nullable();
            $table->text('recomendaciones_tutor')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_paliativo_id');
            $table->index('fecha_inicio');
            $table->index('resultado');
            $table->index('estado_mascota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuidados_paliativos');
    }
};