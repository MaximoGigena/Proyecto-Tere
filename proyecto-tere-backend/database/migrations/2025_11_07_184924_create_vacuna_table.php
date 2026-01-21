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
        Schema::create('vacunas', function (Blueprint $table) {
            $table->id();
            
            // Relación con el tipo de vacuna (obligatorio según caso de uso)
            $table->foreignId('tipo_vacuna_id')
                  ->constrained('tipos_vacuna')
                  ->onDelete('cascade');
                        
            // Campos obligatorios según el caso de uso
            $table->string('numero_dosis', 50); // Número de dosis
            $table->string('lote_serie'); // Lote / Serie del frasco
            
            // Campo opcional según caso de uso
            $table->date('fecha_proxima_dosis')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('tipo_vacuna_id');
            $table->index('lote_serie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacuna', function (Blueprint $table) {
            //
        });
    }
};
