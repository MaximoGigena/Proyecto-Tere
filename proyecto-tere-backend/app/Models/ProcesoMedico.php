<?php
// app/Models/ProcesoMedico.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcesoMedico extends Model
{
    protected $table = 'procesos_medicos';

    protected $fillable = [
        'mascota_id',
        'veterinario_id',
        'centro_veterinario_id',
        'categoria',
        'fecha_aplicacion',
        'observaciones',
        'costo',
        'procesable_type',
        'procesable_id'
    ];

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function centroVeterinario(): BelongsTo
    {
        return $this->belongsTo(CentroVeterinario::class);
    }

    // La relación polimórfica: Esto accede al modelo específico (Vacuna, Cirugia, etc.)
    public function procesable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relación polimórfica con ProcesoMedico
     */
    public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }

    /**
     * Relación con FarmacoAsociado
     */
    public function farmacosAsociados()
    {
        return $this->morphMany(FarmacoAsociado::class, 'farmacable');
    }
}