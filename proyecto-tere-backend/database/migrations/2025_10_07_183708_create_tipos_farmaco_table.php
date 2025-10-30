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
        Schema::create('tipos_farmaco', function (Blueprint $table) {
            $table->id();
            
            // Datos obligatorios
            $table->string('nombre_comercial');
            $table->string('nombre_generico');
            $table->text('composicion');
            $table->enum('categoria', [
                'analgesico', 
                'antibiotico', 
                'antiparasitario', 
                'antiinflamatorio', 
                'antifungico', 
                'antiviral', 
                'anestesico', 
                'otro'
            ]);
            $table->string('categoria_otro')->nullable();
            $table->enum('especie', [
                'canino', 
                'felino', 
                'ave', 
                'roedor', 
                'exotico', 
                'todos', 
                'ninguna'
            ]);
            $table->decimal('dosis', 8, 2);
            $table->enum('unidad', ['mg', 'ml', 'UI', 'mcg', 'gotas']);
            $table->enum('frecuencia_unidad', ['kg', 'dosis']);
            $table->string('frecuencia');
            $table->enum('via_administracion', [
                'oral',
                'subcutanea', 
                'intramuscular', 
                'intravenosa', 
                'topica', 
                'oftalmica', 
                'otica', 
                'otra'
            ]);
            $table->text('indicaciones_clinicas');
            
            // Datos opcionales
            $table->text('contraindicaciones')->nullable();
            $table->text('interacciones_medicamentosas')->nullable();
            $table->text('reacciones_adversas')->nullable();
            $table->string('fabricante')->nullable();
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
            $table->index('categoria');
            $table->index('especie');
            $table->index('via_administracion');
            $table->index('nombre_comercial');
            $table->index('nombre_generico');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_farmaco');
    }
};
