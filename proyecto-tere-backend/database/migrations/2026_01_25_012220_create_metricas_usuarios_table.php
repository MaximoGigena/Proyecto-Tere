<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metricas_usuarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('tipo_reporte'); // volumen, crecimiento, actividad, comportamiento, calidad
            $table->string('tipo_usuario')->nullable(); // adoptante, veterinario, admin, ong
            $table->json('datos'); // Almacena todos los KPIs como JSON
            $table->integer('total_usuarios')->default(0);
            $table->integer('usuarios_activos')->default(0);
            $table->integer('usuarios_nuevos')->default(0);
            $table->decimal('tasa_crecimiento', 5, 2)->default(0);
            $table->decimal('dau', 10, 2)->default(0);
            $table->decimal('mau', 10, 2)->default(0);
            $table->decimal('ratio_dau_mau', 5, 2)->default(0);
            $table->timestamps();
            
            $table->index(['fecha', 'tipo_reporte']);
            $table->index(['tipo_usuario', 'fecha']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('metricas_usuarios');
    }
};
