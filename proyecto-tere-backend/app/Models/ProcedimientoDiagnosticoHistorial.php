<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedimientoDiagnosticoHistorial extends Model
{
    protected $table = 'procedimiento_diagnostico_historial';

    protected $fillable = [
        'procedimiento_diagnostico_id',
        'accion',
        'detalles_cambio',
        'usuario_id',
        'fecha_cambio'
    ];

    protected $casts = [
        'detalles_cambio' => 'array',
        'fecha_cambio' => 'datetime'
    ];

    // Relación con el procedimiento_diagnostico
    public function procedimientoDiagnostico(): BelongsTo
    {
        return $this->belongsTo(ProcedimientoDiagnostico::class);
    }

    // Relación con el usuario que realizó el cambio
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Scope para filtrar por tipo de acción
    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    // Método para registrar un cambio
    public static function registrarCambio(
        ProcedimientoDiagnostico $procedimientoDiagnostico,
        string $accion,
        array $detalles = null,
        int $usuarioId = null
    ) {
        return self::create([
            'procedimiento_diagnostico_id' => $procedimientoDiagnostico->id,
            'accion' => $accion,
            'detalles_cambio' => $detalles,
            'usuario_id' => $usuarioId ?? auth()->id(),
            'fecha_cambio' => now()
        ]);
    }
}