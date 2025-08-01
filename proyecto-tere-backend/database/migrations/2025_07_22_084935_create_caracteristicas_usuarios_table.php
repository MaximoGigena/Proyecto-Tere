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
            $table->string('tipoVivienda');
            $table->string('ocupacion');
            $table->string('experiencia');
            $table->string('convivenciaNiños');
            $table->string('convivenciaMascotas');
            $table->string('descripción');
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
