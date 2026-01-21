<?php
// app/Models/Vacuna.php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoVacuna;
use App\Models\ProcesoMedico;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vacuna extends Model
{

    use SoftDeletes; 

    protected $table = 'vacunas';

    protected $fillable = [
        'tipo_vacuna_id',
        'numero_dosis',
        'lote_serie',
        'fecha_proxima_dosis'
    ];

    protected $casts = [
        'fecha_proxima_dosis' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con el tipo de vacuna
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoVacuna::class, 'tipo_vacuna_id');
    }

    /**
     * Relación polimórfica con ProcesoMedico
     * Una vacuna tiene un proceso médico asociado
     */
    public function procesoMedico(): MorphOne
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }

    /**
     * Accessor para obtener el nombre del tipo de vacuna
     */
    public function getNombreTipoAttribute(): string
    {
        return $this->tipo->nombre ?? 'Tipo no especificado';
    }

    /**
     * Scope para vacunas que tienen próxima dosis programada
     */
    public function scopeConProximaDosis($query)
    {
        return $query->whereNotNull('fecha_proxima_dosis');
    }

    /**
     * Scope para vacunas por lote
     */
    public function scopePorLote($query, $lote)
    {
        return $query->where('lote_serie', 'like', "%{$lote}%");
    }

    /**
     * Verificar si tiene próxima dosis pendiente
     */
    public function getTieneProximaDosisAttribute(): bool
    {
        return !is_null($this->fecha_proxima_dosis);
    }

    /**
     * Verificar si la próxima dosis está vencida
     */
    public function getProximaDosisVencidaAttribute(): bool
    {
        if (!$this->tiene_proxima_dosis) {
            return false;
        }

        return $this->fecha_proxima_dosis->isPast();
    }

    /**
     * Scope para excluir vacunas eliminadas lógicamente
     */
    public function scopeActivas($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope para incluir solo vacunas eliminadas
     */
    public function scopeEliminadas($query)
    {
        return $query->whereNotNull('deleted_at');
    }
}