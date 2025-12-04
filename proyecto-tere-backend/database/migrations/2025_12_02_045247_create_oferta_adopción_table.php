<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ofertas_adopcion', function (Blueprint $table) {
            // Identificador único
            $table->id('id_oferta')->comment('Identificador único de la oferta');
            
            // Claves foráneas
            $table->unsignedBigInteger('id_mascota')->comment('Mascota a la que pertenece la oferta');
            $table->unsignedBigInteger('id_usuario_responsable')->comment('Tutor/rescatista o entidad que publica la oferta');
            
            // Estado de la oferta
            $table->enum('estado_oferta', [
                'publicada', 
                'pausada', 
                'en_proceso', 
                'cerrada', 
                'cancelada'
            ])->default('publicada')->comment('Estado de la oferta de adopción');
            
            // Permisos
            $table->boolean('permiso_historial_medico')
                  ->default(false)
                  ->comment('Permiso para acceder al historial médico de la mascota');
                  
            $table->boolean('permiso_contacto_tutor')
                  ->default(false)
                  ->comment('Permiso para contactar al tutor/rescatista de la mascota');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes()->comment('Fecha de eliminación lógica');
            
            // Índices y restricciones
            $table->foreign('id_mascota')
                  ->references('id')
                  ->on('mascotas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
                  
            $table->foreign('id_usuario_responsable')
                  ->references('id')
                  ->on('usuarios')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
                  
            $table->index('estado_oferta');
            $table->index(['id_mascota', 'estado_oferta']);
            $table->index(['id_usuario_responsable', 'estado_oferta']);
            
        });
        
        // Comentarios adicionales para columnas específicas (PostgreSQL)
      
        DB::statement("COMMENT ON TABLE ofertas_adopcion IS 'Tabla que almacena las ofertas de adopción de mascotas'");
        DB::statement("COMMENT ON COLUMN ofertas_adopcion.estado_oferta IS 'Estados posibles: publicada, pausada, en_proceso, cerrada, cancelada'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas_adopcion');
    }
};
