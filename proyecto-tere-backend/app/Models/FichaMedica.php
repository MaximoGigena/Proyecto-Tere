<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaMedica extends Model
{

    protected $table = 'ficha_medica';

    protected $fillable = [
        'mascota_id',
        'color_y_senas',
        'peso_actual',
        'tipo_sanguineo',
        'numero_chip',
        'fecha_ultima_actualizacion_peso'
    ];

    protected $casts = [
        'peso_actual' => 'decimal:2',
        'fecha_ultima_actualizacion_peso' => 'date'
    ];

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }

    // Método para actualizar peso con fecha
    public function actualizarPeso(float $nuevoPeso): void
    {
        $this->update([
            'peso_actual' => $nuevoPeso,
            'fecha_ultima_actualizacion_peso' => now()
        ]);
    }

    // Accessor para formatear peso
    public function getPesoFormateadoAttribute(): string
    {
        if (!$this->peso_actual) {
            return 'No registrado';
        }
        return number_format($this->peso_actual, 1) . ' kg';
    }
}