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
        Schema::create('procesos_medicos', function (Blueprint $table) {
            $table->id();
    
            // Relación con la mascota
            $table->foreignId('mascota_id')->constrained()->onDelete('cascade');
            
            // Relaciones opcionales con veterinario y centro
            $table->foreignId('veterinario_id')->nullable()->constrained('users')->onDelete('set null'); // Asumiendo que los veterinarios están en la tabla 'users'
            $table->foreignId('centro_veterinario_id')
                ->nullable()
                ->constrained('centros_veterinarios') // 👈 nombre exacto de la tabla
                ->onDelete('set null');
            
            // Tipo general (para filtros rápidos)
            $table->enum('categoria', ['preventivo', 'clinico']);
            
            // Relación polimórfica: Estos dos campos le dicen a Laravel a qué modelo específico apuntar.
            $table->string('procesable_type'); // Ej: 'App\Models\Vacuna', 'App\Models\Cirugia'
            $table->unsignedBigInteger('procesable_id'); // El ID del registro en la tabla específica (ej: `id` en la tabla `vacunas`)
            
            // Metadatos comunes a TODOS los procesos
            $table->date('fecha_aplicacion');
            $table->text('observaciones')->nullable();
            $table->decimal('costo', 8, 2)->nullable(); // Un campo útil que podría servir para todos
            
            $table->timestamps();
            
            // Índices para mejorar el rendimiento de las consultas polimórficas
            $table->index(['procesable_type', 'procesable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_medicos');
    }
};
