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
        Schema::create('mascotas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('especie', ['perro', 'gato', 'otro']);
            $table->integer('edad');
            $table->enum('unidad_edad', ['Dias', 'Meses', 'Años']);
            $table->enum('sexo', ['macho', 'hembra']);
            $table->foreignId('usuario_id')
            ->references('id')
            ->on('usuarios')
            ->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mascotas');
    }
};
