<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class CaracteristicasMascota extends Model
{

    use Auditable;

    protected $table = 'caracteristicas_mascotas';

    protected $fillable = [
        'mascota_id',
        'tamano',
        'pelaje',
        'alimentacion',
        'energia',
        'ejercicio', // NUEVO CAMPO
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