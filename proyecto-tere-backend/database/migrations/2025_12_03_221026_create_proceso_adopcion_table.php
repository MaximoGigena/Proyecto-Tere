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
        Schema::create('proceso_adopcion', function (Blueprint $table) {

            // Identificador único del proceso
            $table->id('id_proceso')->comment('Identificador único del proceso de adopción');

            // Relación con la oferta seleccionada
            $table->unsignedBigInteger('id_oferta')->comment('Oferta de adopción involucrada');

            // Relación con la solicitud aceptada
            $table->unsignedBigInteger('id_solicitud')->comment('Solicitud que inició el proceso');

            // Tutor / responsable original
            $table->unsignedBigInteger('id_usuario_tutor')->comment('Usuario responsable que ofrece la mascota');

            // Usuario adoptante
            $table->unsignedBigInteger('id_usuario_adoptante')->comment('Usuario solicitante que adopta');

            // Estado del proceso
            $table->enum('estado_proceso', [
                'iniciado',
                'entrevista',
                'evaluacion',
                'aprobado',
                'rechazado',
                'cancelado',
                'finalizado'
            ])->default('iniciado')->comment('Estado actual del proceso de adopción');

            // Fechas del proceso
            $table->timestamp('fecha_inicio')->useCurrent()->comment('Fecha y hora de inicio del proceso');
            $table->timestamp('fecha_fin')->nullable()->comment('Fecha en que se finalizó o canceló el proceso');

            // Confirmaciones
            $table->boolean('confirmacion_tutor')->default(false)->comment('El tutor confirma la entrega');
            $table->boolean('confirmacion_adoptante')->default(false)->comment('El adoptante confirma la recepción de la mascota');

            $table->text('notas_tutor')->nullable(); // ← ESTA COLUMNA
            $table->text('notas_adoptante')->nullable(); // ← Y ESTA
            $table->text('motivo_rechazo')->nullable()->comment('Motivo por el cual se rechazó el proceso');
            // Timestamps de Laravel
            $table->timestamps();

            // ------------------
            // Claves foráneas
            // ------------------
            $table->foreign('id_oferta')
                ->references('id_oferta')
                ->on('ofertas_adopcion')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('id_solicitud')
                ->references('idSolicitud')
                ->on('solicitudes_adopcion')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('id_usuario_tutor')
                ->references('id')
                ->on('usuarios')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('id_usuario_adoptante')
                ->references('id')
                ->on('usuarios')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Índices
            $table->index('estado_proceso');
            $table->index(['id_oferta', 'id_solicitud']);
        });

        // Comentario para la tabla
        DB::statement("COMMENT ON TABLE proceso_adopcion IS 'Gestión completa del proceso de adopción entre tutor y adoptante'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proceso_adopcion');
    }
};
