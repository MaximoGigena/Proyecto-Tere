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
       Schema::create('sanciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('denuncia_id')->nullable()->constrained('denuncias')->onDelete('set null');
            $table->enum('tipo', App\Models\Sancion::TIPOS);
            $table->enum('nivel', array_keys(App\Models\Sancion::NIVELES));
            $table->string('razon');
            $table->text('descripcion')->nullable();
            $table->integer('duracion_dias')->nullable();
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->json('restricciones')->nullable();
            $table->enum('estado', App\Models\Sancion::ESTADOS)->default('ACTIVA');
            $table->text('notas_admin')->nullable();
            $table->timestamps();
            
            $table->index(['usuario_id', 'estado']);
            $table->index('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanciones');
    }
};
