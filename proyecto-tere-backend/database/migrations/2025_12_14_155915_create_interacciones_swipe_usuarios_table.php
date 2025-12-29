<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacciones_swipe_usuarios', function (Blueprint $table) {
            $table->id();
            
            // Claves foráneas
            $table->unsignedBigInteger('usuario_id')->comment('ID del usuario que interactúa');
            $table->unsignedBigInteger('mascota_id')->comment('ID de la mascota interactuada');
            $table->unsignedBigInteger('oferta_id')->nullable()->comment('ID de la oferta relacionada');
            
            // Tipo de interacción
            $table->enum('tipo_interaccion', ['like', 'dislike', 'vista'])
                  ->default('vista')
                  ->comment('Tipo de interacción: like, dislike o vista');
            
            // Fecha de interacción
            $table->timestamp('fecha_interaccion')->useCurrent();
            
            // Timestamps
            $table->timestamps();
            
            // Índices y restricciones
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('mascota_id')
                  ->references('id')
                  ->on('mascotas')
                  ->onDelete('cascade');
                  
            $table->foreign('oferta_id')
                  ->references('id_oferta')
                  ->on('ofertas_adopcion')
                  ->onDelete('cascade');
                  
            // Índice único para evitar duplicados
            $table->unique(['usuario_id', 'mascota_id', 'oferta_id'], 'unique_interaccion');
            
            // Índices para búsquedas
            $table->index('usuario_id');
            $table->index('mascota_id');
            $table->index('oferta_id');
            $table->index('tipo_interaccion');
            $table->index('fecha_interaccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interacciones_swipe_usuarios');
    }
};
