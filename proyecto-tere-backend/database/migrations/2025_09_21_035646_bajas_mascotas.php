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
       Schema::create('bajas_mascotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mascota_id')
                ->constrained('mascotas')
                ->onDelete('cascade'); // Más limpio
            
            $table->foreignId('motivo_baja_id')
                ->constrained('motivos_baja')
                ->onDelete('restrict'); // Evita eliminar motivos usados
            
            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->onDelete('cascade'); // o 'restrict' según tu lógica
            
            $table->text('observacion')->nullable();
            $table->timestamps();
            
            // Índice único para evitar múltiples bajas de la misma mascota
            $table->unique('mascota_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bajas_mascotas');
    }
};
