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
        Schema::create('revisiones', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de revisión (obligatorio según vista)
            $table->foreignId('tipo_revision_id')
                  ->constrained('tipos_revision')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->dateTime('fecha_revision'); // Fecha de revisión (datetime-local)
            $table->enum('nivel_urgencia', [
                'rutinaria', 
                'preventiva', 
                'urgencia', 
                'emergencia'
            ])->default('rutinaria');
            
            // Campos opcionales según la vista
            $table->string('motivo_consulta', 500)->nullable();
            $table->string('diagnostico', 500)->nullable();
            $table->date('fecha_proxima_revision')->nullable();
            $table->text('indicaciones_medicas')->nullable(); // Indicaciones o conducta médica
            $table->text('recomendaciones_tutor')->nullable(); // Recomendaciones al tutor
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_revision_id');
            $table->index('fecha_revision');
            $table->index('nivel_urgencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisiones');
    }
};
