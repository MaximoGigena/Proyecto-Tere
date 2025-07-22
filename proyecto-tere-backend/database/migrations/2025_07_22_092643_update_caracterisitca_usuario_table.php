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
    Schema::table('caracteristicas_usuario', function (Blueprint $table) {
        // Primero añade la columna como nullable
        $table->unsignedBigInteger('usuario_id')->nullable();
        
        // Luego añade la foreign key
        $table->foreign('usuario_id')
              ->references('id')
              ->on('usuarios')
              ->onDelete('cascade');
    });
}

    public function down(): void
    {
        Schema::table('caracteristicas_usuario', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');
        });
    }
};
