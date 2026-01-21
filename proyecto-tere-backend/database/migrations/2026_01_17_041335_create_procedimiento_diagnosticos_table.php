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
        Schema::create('procedimiento_diagnosticos', function (Blueprint $table) {
            $table->id();
            
            // Referencia al procedimiento médico (cirugía, terapia, etc.)
            $table->unsignedBigInteger('procedimiento_id');
            $table->string('procedimiento_type'); // Para polimórfica: 'App\Models\Cirugia', 'App\Models\Terapia', etc.
            
            // Campos para el diagnóstico (polimórfico)
            $table->unsignedBigInteger('diagnostico_id');
            $table->string('diagnostico_type'); // 'App\Models\Diagnostico' o 'App\Models\TipoDiagnostico'
            
            // Información adicional sobre el diagnóstico en este procedimiento
            $table->enum('estado', ['activo', 'resuelto', 'cronico', 'seguimiento', 'sospecha'])->default('activo');
            $table->enum('relevancia', ['primario', 'secundario', 'asociado'])->default('primario');
            $table->text('observaciones')->nullable();
            
            // Metadatos del diagnóstico al momento de la asociación (snapshot)
            $table->string('nombre_diagnostico'); // Snapshot del nombre
            $table->string('evolucion')->nullable(); // Snapshot de la evolución
            $table->string('clasificacion')->nullable(); // Snapshot de la clasificación
            $table->text('sintomas_caracteristicos')->nullable(); // Snapshot de síntomas
            
            // Información sobre quién y cuándo
            $table->unsignedBigInteger('veterinario_id')->nullable();
            $table->timestamp('fecha_asociacion')->useCurrent();
            
            // Soft deletes para mantener historial
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para mejor rendimiento
            $table->index(['procedimiento_id', 'procedimiento_type']);
            $table->index(['diagnostico_id', 'diagnostico_type']);
            $table->index('estado');
            $table->index('relevancia');
            $table->index('veterinario_id');
            
            // Llaves foráneas
            $table->foreign('veterinario_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Comentario para documentación
            $table->comment('Tabla puente que asocia diagnósticos a procedimientos médicos. Los diagnósticos pueden ser del catálogo (TipoDiagnostico) o específicos de la mascota (Diagnostico).');
        });
        
        // Tabla adicional para registrar cambios en los diagnósticos asociados
        Schema::create('procedimiento_diagnostico_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procedimiento_diagnostico_id');
            $table->enum('accion', ['creado', 'actualizado', 'resuelto', 'reactivado', 'eliminado']);
            $table->json('detalles_cambio')->nullable(); // Snapshot de los cambios
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->timestamps();
            
            $table->foreign('procedimiento_diagnostico_id')
                  ->references('id')
                  ->on('procedimiento_diagnosticos')
                  ->onDelete('cascade');
                  
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimiento_diagnostico_historial');
        Schema::dropIfExists('procedimiento_diagnosticos');
    }
};
