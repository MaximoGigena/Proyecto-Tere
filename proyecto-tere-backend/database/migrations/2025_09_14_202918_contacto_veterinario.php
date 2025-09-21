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
        Schema::create('contacto_veterinario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veterinario_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->enum('tipo', ['telefono', 'email']);
            $table->string('valor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('contacto_veterinario');
    }
};
