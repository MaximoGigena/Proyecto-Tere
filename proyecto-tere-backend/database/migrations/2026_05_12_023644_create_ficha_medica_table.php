<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichaMedicaTable extends Migration
{
    public function up()
    {
        Schema::create('ficha_medica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mascota_id')->constrained()->onDelete('cascade');
            $table->text('color_y_senas')->nullable();
            $table->decimal('peso_actual', 5, 2)->nullable();
            $table->string('tipo_sanguineo', 20)->nullable();
            $table->string('numero_chip', 50)->nullable();
            $table->date('fecha_ultima_actualizacion_peso')->nullable();
            $table->timestamps();
            
            $table->unique('mascota_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ficha_medica');
    }
}