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
        Schema::create('historial_sanciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sancion_id')->constrained('sanciones')->onDelete('cascade');
            $table->enum('accion', App\Models\HistorialSancion::ACCIONES);
            $table->json('detalles')->nullable();
            $table->foreignId('realizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['usuario_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_sanciones');
    }
};
