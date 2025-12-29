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
        Schema::create('caracteristicas_mascotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mascota_id')->constrained()->onDelete('cascade');
            
            // Campos opcionales
            $table->enum('tamano', ['pequeño', 'mediano', 'grande'])->nullable();
            $table->enum('pelaje', ['corto', 'medio', 'largo'])->nullable();
            $table->enum('alimentacion', [
                'Alimentación comercial',
                'Dieta natural', 
                'Dieta mixta',
                'Dietas especiales'
            ])->nullable();
            $table->enum('energia', ['Bajo', 'Medio', 'Alto'])->nullable();
            $table->enum('ejercicio', [ 
                'Diariamente',
                'Varias veces por semana',
                'Semanalmente',
                'Ocasionalmente',
                'No realiza ejercicio'
            ])->nullable();
            $table->enum('comportamiento_animales', [
                'Social',
                'Territorial',
                'Depredador',
                'Temeroso',
                'Agresivo',
                'Indeterminado'
            ])->nullable();
            $table->enum('comportamiento_ninos', [
                'Paciente',
                'Juguetón',
                'Temeroso',
                'Estresado',
                'Agresivo',
                'Indeterminado'
            ])->nullable();
            $table->enum('personalidad', [
                'Amigable',
                'Reservado',
                'Curioso',
                'Nervioso',
                'Territorial',
                'Tranquilo',
                'Protector'
            ])->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caracteristicas_mascotas');
    }
};
