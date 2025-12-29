// database/migrations/xxxx_xx_xx_xxxxxx_create_mensajes_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id('mensaje_id');
            $table->foreignId('chat_id')->constrained('chats', 'chat_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->text('contenido');
            $table->string('tipo')->default('texto'); // texto, imagen, documento, etc.
            $table->string('url_adjunto')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_en')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index(['chat_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};