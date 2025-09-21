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
        Schema::create('administradores', function (Blueprint $table) {
            $table->id(); // ID autoincremental
            $table->string('nombre_completo');
            $table->string('nivel_acceso'); 
            $table->timestamp('ultimo_login')->nullable(); // Último login opcional
            $table->boolean('activo')->default(true); // Estado activo/inactivo
            $table->string('user_type')->default('free');
            $table->string('google_id')->nullable();
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administradores');
    }
};
