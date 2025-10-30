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
        Schema::create('centros_veterinarios', function (Blueprint $table) {
            $table->id();
            
            // Datos obligatorios
            $table->string('nombre');
            $table->string('identificacion'); // CUIT/CUIL/DNI
            $table->text('direccion');
            $table->string('telefono');
            $table->string('email');
            $table->text('especialidades_medicas');
            $table->string('logo_path')->nullable();
            
            // Datos opcionales
            $table->string('matricula_establecimiento')->nullable();
            $table->string('horarios_atencion')->nullable();
            $table->string('web_redes_sociales')->nullable();
            
            // Estado del registro
            $table->boolean('activo')->default(true);
            $table->softDeletes();
        
            
            // Timestamps
            $table->timestamps();
            
            $table->foreignId('veterinario_id')
                  ->nullable()
                  ->constrained('veterinarios')
                  ->onDelete('cascade');
                  
            // Índices
            $table->index('identificacion');
            $table->index('email');
            $table->unique('identificacion');
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centros_veterinarios');
    }
};
