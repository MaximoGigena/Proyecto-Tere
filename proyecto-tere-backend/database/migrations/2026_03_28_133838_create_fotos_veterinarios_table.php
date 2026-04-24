<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos_veterinarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veterinario_id')->constrained('veterinarios')->onDelete('cascade');
            $table->string('ruta'); // Ruta de la foto
            $table->integer('orden')->default(0); // Orden de visualización
            $table->string('tipo')->default('perfil'); // perfil, documento, etc.
            $table->boolean('activa')->default(true);
            $table->timestamps();
            
            $table->index(['veterinario_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_veterinarios');
    }
};