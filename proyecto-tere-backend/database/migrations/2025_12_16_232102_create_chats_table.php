// database/migrations/xxxx_xx_xx_xxxxxx_create_chats_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id('chat_id');
            $table->foreignId('user1_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes_adopcion', 'idSolicitud')->onDelete('cascade');
            $table->string('ultimo_mensaje')->nullable();
            $table->timestamp('ultimo_mensaje_en')->nullable();
            $table->integer('total_interacciones')->default(0)->after('solicitud_id');
            $table->integer('interacciones_usuario1')->default(0)->after('total_interacciones');
            $table->integer('interacciones_usuario2')->default(0)->after('interacciones_usuario1');
            $table->boolean('listo_para_adopcion')->default(false)->after('interacciones_usuario2');
            $table->timestamp('fecha_habilitado_adopcion')->nullable()->after('listo_para_adopcion');
            $table->boolean('user1_deleted')->default(false);
            $table->boolean('user2_deleted')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Índices únicos
            $table->unique(['user1_id', 'user2_id', 'solicitud_id']);
            $table->index(['user1_id', 'ultimo_mensaje_en']);
            $table->index(['user2_id', 'ultimo_mensaje_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};