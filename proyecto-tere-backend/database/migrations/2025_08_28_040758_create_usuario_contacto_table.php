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
        Schema::create('usuario_contacto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained()->onDelete('cascade');
            $table->string('dni', 20)->nullable()->unique(); // Permitir nulos
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable()->unique(); 
            $table->string('nombre_completo', 200)->nullable(); 
            $table->string('telegram_chat_id', 50)->nullable()->unique();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('dni');
            $table->index('email');
            $table->index('nombre_completo');
            $table->index('telegram_chat_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_contacto');
    }
};
