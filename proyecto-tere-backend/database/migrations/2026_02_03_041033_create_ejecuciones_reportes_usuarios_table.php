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
        Schema::create('ejecuciones_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')
                  ->constrained('reportes_usuarios')
                  ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('parametros')->nullable();
            $table->json('resultados')->nullable();
            $table->enum('estado', [
                'pendiente', 'procesando', 'completado', 'fallido', 'cancelado'
            ])->default('pendiente');
            $table->text('mensaje_error')->nullable();
            $table->float('tiempo_ejecucion')->nullable();
            $table->integer('tamano_datos')->nullable();
            $table->enum('formato', ['json', 'pdf', 'excel', 'csv'])->default('json');
            $table->string('ruta_archivo')->nullable();
            $table->timestamps();
            
            $table->index(['reporte_id', 'estado', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ejecuciones_reportes');
    }
};
