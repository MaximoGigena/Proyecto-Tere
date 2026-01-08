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

            $table->foreignId('chat_id')
                ->constrained('chats', 'chat_id')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->onDelete('cascade');

            // Contenido opcional
            $table->text('contenido')->nullable();

            // Tipo funcional del mensaje
            $table->string('tipo'); 
            // texto | sistema | documento | factura | ficha_clinica | notificacion | externo

            // Canal de envío
            $table->string('canal')->default('interno');
            // interno | whatsapp | telegram | email

            // Adjuntos o referencias
            $table->string('url_adjunto')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('referencia_tipo')->nullable();
            // factura, procedimiento, denuncia, historial_clinico

            // Estado
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_en')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['chat_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['tipo']);
            $table->index(['canal']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};