<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcedimientoDiagnostico extends Model
{
    use SoftDeletes;

    protected $table = 'procedimiento_diagnosticos';

    protected $fillable = [
        'procedimiento_id',
        'procedimiento_type',
        'diagnostico_id',
        'diagnostico_type',
        'estado',
        'relevancia',
        'observaciones',
        'nombre_diagnostico',
        'evolucion',
        'clasificacion',
        'sintomas_caracteristicos',
        'veterinario_id',
        'fecha_asociacion'
    ];

    protected $casts = [
        'fecha_asociacion' => 'datetime',
        'sintomas_caracteristicos' => 'array'
    ];

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::created(function ($procedimientoDiagnostico) {
            ProcedimientoDiagnosticoHistorial::registrarCambio(
                $procedimientoDiagnostico,
                'creado',
                [
                    'diagnostico_nombre' => $procedimientoDiagnostico->nombre_diagnostico,
                    'relevancia' => $procedimientoDiagnostico->relevancia,
                    'estado' => $procedimientoDiagnostico->estado
                ]
            );
        });

        static::updated(function ($procedimientoDiagnostico) {
            $cambios = $procedimientoDiagnostico->getChanges();
            
            // No registrar cambios en timestamps
            unset($cambios['updated_at']);
            
            if (!empty($cambios)) {
                ProcedimientoDiagnosticoHistorial::registrarCambio(
                    $procedimientoDiagnostico,
                    'actualizado',
                    [
                        'cambios' => $cambios,
                        'anterior' => $procedimientoDiagnostico->getOriginal()
                    ]
                );
            }
        });

        static::deleted(function ($procedimientoDiagnostico) {
            if ($procedimientoDiagnostico->isForceDeleting()) {
                ProcedimientoDiagnosticoHistorial::registrarCambio(
                    $procedimientoDiagnostico,
                    'eliminado_permanentemente',
                    [
                        'diagnostico_nombre' => $procedimientoDiagnostico->nombre_diagnostico
                    ]
                );
            } else {
                ProcedimientoDiagnosticoHistorial::registrarCambio(
                    $procedimientoDiagnostico,
                    'eliminado',
                    [
                        'diagnostico_nombre' => $procedimientoDiagnostico->nombre_diagnostico
                    ]
                );
            }
        });
    }

    // Relación polimórfica con el procedimiento
    public function procedimiento(): MorphTo
    {
        return $this->morphTo();
    }

    // Relación polimórfica con el diagnóstico
    public function diagnostico(): MorphTo
    {
        return $this->morphTo();
    }

    // Relación con el veterinario
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    // Historial de cambios
    public function historial(): HasMany
    {
        return $this->hasMany(ProcedimientoDiagnosticoHistorial::class);
    }

    // Scope para diagnósticos de catálogo
    public function scopeDeCatalogo($query)
    {
        return $query->where('diagnostico_type', 'App\Models\TipoDiagnostico');
    }

    // Scope para diagnósticos de mascota
    public function scopeDeMascota($query)
    {
        return $query->where('diagnostico_type', 'App\Models\Diagnostico');
    }

    // Método para crear snapshot del diagnóstico
    public function tomarSnapshotDelDiagnostico($diagnostico)
    {
        $this->nombre_diagnostico = $diagnostico->nombre;
        $this->evolucion = $diagnostico->evolucion;
        $this->clasificacion = $diagnostico->clasificacion;
        $this->sintomas_caracteristicos = $diagnostico->sintomas_caracteristicos;
    }

    // Métodos para cambiar estado
    public function marcarComoResuelto(string $observaciones = null)
    {
        $this->update([
            'estado' => 'resuelto',
            'observaciones' => $observaciones ?: $this->observaciones
        ]);

        ProcedimientoDiagnosticoHistorial::registrarCambio(
            $this,
            'resuelto',
            ['observaciones' => $observaciones]
        );

        return $this;
    }

    public function reactivar(string $observaciones = null)
    {
        $this->update([
            'estado' => 'activo',
            'observaciones' => $observaciones ?: $this->observaciones
        ]);

        ProcedimientoDiagnosticoHistorial::registrarCambio(
            $this,
            'reactivado',
            ['observaciones' => $observaciones]
        );

        return $this;
    }
}