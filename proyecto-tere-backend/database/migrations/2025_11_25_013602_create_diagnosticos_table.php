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
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de diagnóstico (obligatorio según vista)
            $table->foreignId('tipo_diagnostico_id')
                  ->constrained('tipos_diagnostico')
                  ->onDelete('cascade');
            
            // Campos obligatorios según la vista
            $table->string('nombre'); // Ej: Insuficiencia renal, parvovirus, etc.
            $table->date('fecha_diagnostico');
            $table->enum('estado', ['activo', 'resuelto', 'cronico', 'seguimiento', 'sospecha']);
            
            // Campos opcionales según la vista
            $table->text('diagnosticos_diferenciales')->nullable();
            $table->text('examenes_complementarios')->nullable();
            $table->text('conducta_terapeutica')->nullable();
            $table->text('observaciones')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_diagnostico_id');
            $table->index('fecha_diagnostico');
            $table->index('estado');
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
    }
};