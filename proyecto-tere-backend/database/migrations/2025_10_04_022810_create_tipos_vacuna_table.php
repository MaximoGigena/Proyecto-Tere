<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposVacunaTable extends Migration
{
    public function up()
    {
        Schema::create('tipos_vacuna', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('enfermedades');
            $table->enum('especie', ['canino', 'felino', 'ave', 'roedor', 'exotico', 'todos']);
            $table->decimal('edad_minima', 5, 2);
            $table->enum('edad_unidad', ['semanas', 'meses', 'años']);
            $table->decimal('dosis', 5, 2);
            $table->enum('dosis_unidad', ['ml', 'dosis', 'gotas']);
            $table->enum('via_administracion', ['subcutanea', 'intramuscular', 'oral', 'nasal']);
            $table->enum('frecuencia', ['unica', 'anual', 'semestral', 'personalizada']);
            $table->string('frecuencia_personalizada')->nullable();
            $table->enum('obligatoriedad', ['obligatoria', 'opcional', 'depende']);
            $table->string('intervalo_dosis')->nullable();
            $table->string('fabricante')->nullable();
            $table->text('riesgos')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->string('lote')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('veterinario_id')
                  ->nullable()
                  ->constrained('veterinarios')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tipos_vacuna');
    }
}