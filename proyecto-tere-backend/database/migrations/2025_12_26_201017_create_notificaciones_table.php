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
       Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            
            // Usuario destinatario
            $table->unsignedBigInteger('usuario_id');
            
            // Tipo de notificación
            $table->enum('tipo', [
                'ADVERTENCIA',
                'SANCION',
                'SISTEMA',
                'MENSAJE',
                'PROCEDIMIENTO',
                'DENUNCIA',
                'OFERTA',
                'SOLICITUD',
                'ALERTA'
            ])->default('SISTEMA');
            
            // Contenido
            $table->string('titulo', 255);
            $table->text('contenido');
            
            // Origen de la notificación
            $table->enum('origen', [
                'SISTEMA',
                'USUARIO',
                'ADMINISTRADOR',
                'REFUGIO',
                'MUNICIPIO',
                'VETERINARIO',
                'MODERADOR'
            ])->default('SISTEMA');
            
            // Referencia a entidad relacionada
            $table->string('referencia_tipo', 100)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            
            // Estado
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_lectura')->nullable();
            $table->boolean('activa')->default(true);
            
            // Índices y timestamps
            $table->timestamps();
            
            // Foreign key
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            // Índices para optimización
            $table->index(['usuario_id', 'leida']);
            $table->index(['referencia_tipo', 'referencia_id']);
            $table->index(['activa', 'created_at']);
            $table->index('tipo');
        });
        
        // Comentarios de la tabla (opcional, para documentación)
        DB::statement("COMMENT ON TABLE notificaciones IS 'Tabla de notificaciones para usuarios del sistema'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
