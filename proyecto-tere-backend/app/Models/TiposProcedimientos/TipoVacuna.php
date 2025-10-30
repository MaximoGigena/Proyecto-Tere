<?php
// app/Models/TipoVacuna.php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoVacuna extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_vacuna';

    protected $fillable = [
        'nombre',
        'enfermedades',
        'especie',
        'edad_minima',
        'edad_unidad',
        'dosis',
        'dosis_unidad',
        'via_administracion',
        'frecuencia',
        'frecuencia_personalizada',
        'obligatoriedad',
        'intervalo_dosis',
        'fabricante',
        'riesgos',
        'recomendaciones',
        'lote',
        'activo',
        'veterinario_id'
    ];

    protected $casts = [
        'edad_minima' => 'decimal:2',
        'dosis' => 'decimal:2',
        'activo' => 'boolean'
    ];

    // Relación con Veterinario
    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    // Relaciones si son necesarias en el futuro
}