<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaracteristicasVeterinario extends Model
{
    use HasFactory;

    protected $table = 'caracteristicas_veterinario';

    protected $fillable = [
        'veterinario_id',
        'anos_experiencia',
        'descripcion'
    ];

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Veterinario::class);
    }
}