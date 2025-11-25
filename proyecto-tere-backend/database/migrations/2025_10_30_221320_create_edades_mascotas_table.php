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
        // Crear tabla de edades
        Schema::create('edades_mascotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mascota_id')
                  ->unique()
                  ->constrained('mascotas')
                  ->onDelete('cascade');
            $table->integer('dias')->nullable();
            $table->integer('meses')->nullable();
            $table->integer('años')->nullable();
            $table->string('edad_formateada')->nullable();
            $table->timestamp('ultima_actualizacion')->useCurrent();
            $table->timestamps();
        });

        // Eliminar columna 'edad_actual' de la tabla mascotas
        Schema::table('mascotas', function (Blueprint $table) {
            if (Schema::hasColumn('mascotas', 'edad_actual')) {
                $table->dropColumn('edad_actual');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la tabla edades_mascotas
        Schema::dropIfExists('edades_mascotas');
    }
};
