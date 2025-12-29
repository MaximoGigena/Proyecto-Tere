<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_cirugias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cirugia_id')
                  ->constrained('cirugias')
                  ->onDelete('cascade');
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('tipo');
            $table->bigInteger('tamano');
            $table->timestamps();
            
            $table->index('cirugia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_cirugias');
    }
};
