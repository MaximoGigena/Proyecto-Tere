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
        Schema::create('historial_transferencias_mascotas', function (Blueprint $table) {
            $table->id('id_transferencia');
            
            // Referencia a la mascota
            $table->unsignedBigInteger('mascota_id')
                  ->comment('Mascota que fue transferida');
            
            // Usuarios involucrados
            $table->unsignedBigInteger('tutor_anterior_id')
                  ->comment('Usuario que cedió la mascota');
                  
            $table->unsignedBigInteger('tutor_nuevo_id')
                  ->comment('Usuario que recibió la mascota');
            
            // Contexto de la transferencia
            $table->unsignedBigInteger('solicitud_adopcion_id')->nullable()
                  ->comment('Solicitud de adopción que originó la transferencia');
                  
            $table->unsignedBigInteger('proceso_adopcion_id')->nullable()
                  ->comment('Proceso de adopción relacionado');
            
            // Detalles de la transferencia
            $table->timestamp('fecha_transferencia')->useCurrent()
                  ->comment('Fecha y hora exacta de la transferencia');
                  
            $table->enum('motivo', [
                'adopcion',
                'devolucion',
                'transferencia_voluntaria', 
                'acogida_temporal',
                'otro'
            ])->default('adopcion')
              ->comment('Motivo de la transferencia');
            
            $table->text('observaciones')->nullable()
                  ->comment('Observaciones o detalles adicionales');
            
            $table->json('datos_adicionales')->nullable()
                  ->comment('Datos adicionales en formato JSON');
            
            // Timestamps
            $table->timestamps();
            
            // ------------------
            // CLAVES FORÁNEAS
            // ------------------
            $table->foreign('mascota_id')
                  ->references('id')
                  ->on('mascotas')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->foreign('tutor_anterior_id')
                  ->references('id')
                  ->on('usuarios')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
                  
            $table->foreign('tutor_nuevo_id')
                  ->references('id')
                  ->on('usuarios')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
                  
            $table->foreign('solicitud_adopcion_id')
                  ->references('idSolicitud')
                  ->on('solicitudes_adopcion')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
                  
            $table->foreign('proceso_adopcion_id')
                  ->references('id_proceso')
                  ->on('proceso_adopcion')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            
            // ------------------
            // ÍNDICES
            // ------------------
            $table->index('mascota_id');
            $table->index('tutor_anterior_id');
            $table->index('tutor_nuevo_id');
            $table->index('fecha_transferencia');
            $table->index(['mascota_id', 'fecha_transferencia']);
            $table->index(['tutor_anterior_id', 'fecha_transferencia']);
            $table->index(['tutor_nuevo_id', 'fecha_transferencia']);
        });
        
        // Comentarios
        DB::statement("COMMENT ON TABLE historial_transferencias_mascotas IS 'Registro histórico completo de todas las transferencias de propiedad de mascotas'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_transferencias_mascotas');
    }
};