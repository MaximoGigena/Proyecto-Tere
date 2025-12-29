<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('denuncias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade'); // Usuario que denuncia
            $table->foreignId('mascota_id')->nullable()->constrained('mascotas')->onDelete('cascade'); // Mascota denunciada
            $table->foreignId('usuario_denunciado_id')->nullable()->constrained('users')->onDelete('cascade'); // Usuario dueño de la mascota
            $table->unsignedBigInteger('id_oferta')->nullable();
            $table->foreign('id_oferta')
                ->references('id_oferta')
                ->on('ofertas_adopcion')
                ->onDelete('cascade');
            $table->string('categoria'); // 'Maltrato Animal', 'Perfil falso', etc.
            $table->string('subcategoria'); // 'Heridas visibles', 'Condiciones insalubres', etc.
            $table->text('descripcion')->nullable(); // Descripción opcional
            $table->enum('estado', ['pendiente', 'en_revision', 'resuelta', 'descarta'])->default('pendiente');
            $table->text('notas_admin')->nullable(); // Notas internas del administrador
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas eficientes
            $table->index('estado');
            $table->index('categoria');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('denuncias');
    }
};