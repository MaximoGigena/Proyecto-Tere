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
        Schema::create('caracteristicas_usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('tipoVivienda')->nullable();
            $table->string('ocupacion')->nullable();
            $table->string('experiencia')->nullable();
            $table->string('convivenciaNiños')->nullable();
            $table->string('convivenciaMascotas')->nullable();
            $table->string('descripción')->nullable();

            // Nueva columna con clave foránea
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('usuarios')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caracteristicas_usuarios');
    }
};

