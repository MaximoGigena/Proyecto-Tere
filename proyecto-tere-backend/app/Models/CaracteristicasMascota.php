<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaracteristicasMascota extends Model
{
    protected $table = 'caracteristicas_mascotas';

    protected $fillable = [
        'mascota_id',
        'tamano',
        'pelaje',
        'alimentacion',
        'energia',
        'comportamiento_animales',
        'comportamiento_ninos',
        'personalidad',
        'descripcion'
    ];

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }
}