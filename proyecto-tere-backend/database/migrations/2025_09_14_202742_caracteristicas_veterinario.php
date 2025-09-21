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
      Schema::create('caracteristicas_veterinario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veterinario_id')
            ->constrained() // 👈 nombre correcto de la tabla
            ->onDelete('cascade')
            ->unique();
            $table->integer('anos_experiencia')->default(0);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caracteristicas_veterinario');
    }
};
