<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('reportes_usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo_reporte', [
                'usuarios', 'veterinarios', 'adopciones', 'metricas', 'personalizado'
            ]);
            $table->json('configuracion')->nullable();
            $table->json('parametros')->nullable();
            $table->json('filtros')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('user_type')->nullable();
            $table->boolean('programado')->default(false);
            $table->enum('frecuencia', [
                'diaria', 'semanal', 'mensual', 'trimestral', 'anual'
            ])->nullable();
            $table->timestamp('ultima_generacion')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'pausado'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tipo_reporte', 'estado']);
            $table->index(['programado', 'ultima_generacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_usuarios');
    }
};
