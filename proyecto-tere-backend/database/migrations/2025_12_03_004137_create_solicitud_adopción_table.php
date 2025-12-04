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
        Schema::create('solicitudes_adopcion', function (Blueprint $table) {
            // Identificador único de la solicitud
            $table->id('idSolicitud')->comment('Identificador único de la solicitud');
            
            // Usuario que envía la solicitud (clave foránea)
            $table->unsignedBigInteger('idUsuarioSolicitante')->comment('Usuario que envía la solicitud');
            
            // Mascota que se desea adoptar (clave foránea)
            $table->unsignedBigInteger('idMascota')->comment('Mascota que se desea adoptar');
            
            // Fecha y hora exacta en que el usuario envió la solicitud
            $table->timestamp('fechaSolicitud')->useCurrent()->comment('Fecha y hora exacta en que el usuario envió la solicitud');
            
            // Estado actual de la solicitud
            $table->enum('estadoSolicitud', [
                'pendiente', 
                'aprobada', 
                'rechazada', 
                'cancelada', 
                'expirada'
            ])->default('pendiente')->comment('Estado actual: pendiente, aprobada, rechazada, cancelada, expirada');
            
            // Booleano que certifica que aceptó los términos
            $table->boolean('aceptóTerminos')->default(false)->comment('Booleano que certifica que aceptó los términos (Ley 14.346, convivencia responsable, etc.)');
            
            // Timestamps automáticos de Laravel
            $table->timestamps();
            
            // Índices y claves foráneas
            $table->foreign('idUsuarioSolicitante')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->foreign('idMascota')
                  ->references('id')
                  ->on('mascotas')
                  ->onUpdate('cascade');
            
            // Índice compuesto para búsquedas eficientes
            $table->index(['idUsuarioSolicitante', 'idMascota']);
            
            // Índice para el estado de la solicitud
            $table->index('estadoSolicitud');
            
            // Restricción única para evitar solicitudes duplicadas del mismo usuario para la misma mascota
            $table->unique(['idUsuarioSolicitante', 'idMascota'], 'solicitud_unica_usuario_mascota');
        });

        // Comentario para la tabla
        DB::statement("COMMENT ON TABLE solicitudes_adopcion IS 'Tabla que almacena las solicitudes de adopción de mascotas'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_adopcion');
    }
};
