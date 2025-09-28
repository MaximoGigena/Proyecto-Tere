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
        Schema::create('solicitudes_veterinarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('email')->unique();
            $table->string('matricula')->unique();
            $table->string('especialidad', 150);
            $table->integer('anos_experiencia')->default(0);
            $table->text('descripcion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email_contacto')->nullable();
            $table->json('fotos')->nullable(); // Array de rutas de fotos
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->text('observaciones')->nullable(); // Para que el admin pueda agregar comentarios
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('estado');
            $table->index('fecha_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_veterinarios');
    }
};
