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
        Schema::create('cirugias', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de cirugía (obligatorio según caso de uso)
            $table->foreignId('tipo_cirugia_id')
                  ->constrained('tipos_cirugia')
                  ->onDelete('cascade');
            
            // Campos obligatorios según el caso de uso
            $table->dateTime('fecha_cirugia');
            $table->enum('resultado', ['satisfactorio', 'complicaciones', 'estable', 'critico']);
            $table->enum('estado_actual', ['recuperacion', 'alta', 'seguimiento', 'hospitalizado']);
            $table->string('diagnostico_causa'); // Diagnóstico o causa que justificó la cirugía
            
            // Campos opcionales según el caso de uso
            $table->date('fecha_control_estimada')->nullable();
            $table->text('descripcion_procedimiento')->nullable();
            $table->text('medicacion_postquirurgica')->nullable();
            $table->text('recomendaciones_tutor')->nullable();
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_cirugia_id');
            $table->index('fecha_cirugia');
            $table->index('resultado');
            $table->index('estado_actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cirugias');
    }
};