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
        Schema::create('seguimientos_adopcion', function (Blueprint $table) {
        $table->id('id_seguimiento');
        $table->unsignedBigInteger('id_proceso');
        $table->unsignedBigInteger('id_usuario')->nullable();
        $table->string('estado_anterior')->nullable();
        $table->string('estado_nuevo');
        $table->text('observaciones');
        $table->enum('tipo_evento', [
            'inicio_proceso',
            'cambio_estado',
            'confirmacion_entrega',
            'comunicacion',
            'visita',
            'documentacion',
            'acuerdo',
            'incidencia',
            'finalizacion_automatica',
            'otro'
        ]);
        $table->json('datos_adicionales')->nullable();
        $table->timestamps();

        $table->foreign('id_proceso')
            ->references('id_proceso')
            ->on('proceso_adopcion')
            ->onDelete('cascade');

        $table->foreign('id_usuario')
            ->references('id')
            ->on('usuarios')
            ->onDelete('set null');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimiento_adopcion');
    }
};
