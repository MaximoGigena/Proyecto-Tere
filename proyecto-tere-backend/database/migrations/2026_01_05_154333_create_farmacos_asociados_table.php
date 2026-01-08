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
        Schema::create('farmacos_asociados', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de fármaco (catálogo del veterinario)
            $table->foreignId('tipo_farmaco_id')
                  ->constrained('tipos_farmaco')
                  ->onDelete('cascade');
            
            // Relación polimórfica: puede ser Cirugia o CuidadoPaliativo
            // morphs() ya crea: farmacable_type (string), farmacable_id (bigint) y un índice compuesto
            $table->morphs('farmacable'); 
            
            // Datos de la prescripción específica (obligatorios según tu vista)
            $table->decimal('dosis_prescrita', 10, 3); // Ej: 5.250
            $table->string('unidad_dosis'); // mg, ml, etc.
            
            // Indicador de dosis única
            $table->boolean('es_dosis_unica')->default(false);
            
            // Frecuencia (si no es dosis única)
            $table->integer('frecuencia_valor')->nullable();
            $table->enum('frecuencia_unidad', ['h', 'd'])->nullable(); // horas, días
            
            // Duración (si no es dosis única)
            $table->integer('duracion_valor')->nullable();
            $table->enum('duracion_unidad', ['min', 'h', 'd'])->nullable(); // minutos, horas, días
            
            // Etapa de aplicación (específico para cirugías)
            $table->enum('etapa_aplicacion', [
                'prequirurgica',
                'transquirurgica', 
                'postquirurgica_inmediata',
                'postquirurgica_tardia'
            ])->nullable();
            
            // Momento de aplicación (específico para cuidados paliativos)
            $table->enum('momento_aplicacion', [
                'inicio',
                'mantenimiento',
                'rescue',
                'final'
            ])->nullable();
            
            // Observaciones adicionales
            $table->text('observaciones')->nullable();
            
            // Metadatos
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_farmaco_id');
            $table->index('etapa_aplicacion');
            $table->index('momento_aplicacion');
            $table->index('es_dosis_unica');
            
            // Índice único para evitar duplicados
            $table->unique([
                'tipo_farmaco_id', 
                'farmacable_type', 
                'farmacable_id'
            ], 'unique_farmaco_asociado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmacos_asociados');
    }
};