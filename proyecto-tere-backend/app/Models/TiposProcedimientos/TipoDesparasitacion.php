<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoDesparasitacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_desparasitacion';

    protected $fillable = [
        'nombre',
        'parasitos',
        'otros_parasitos',
        'via_administracion',
        'especies',
        'edad_minima',
        'edad_unidad',
        'frecuencia',
        'frecuencia_unidad',
        'recomendaciones',
        'riesgos',
        'dosis_recomendada',
        'veterinario_id',
        'activo'
    ];

    protected $casts = [
        'parasitos' => 'array',
        'especies' => 'array',
        'edad_minima' => 'decimal:2',
        'frecuencia' => 'integer',
        'activo' => 'boolean'
    ];

    protected $attributes = [
        'activo' => true 
    ];

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Accesores para facilitar el uso en las vistas
     */
    public function getEdadCompletaAttribute(): string
    {
        return "{$this->edad_minima} {$this->edad_unidad}";
    }

    public function getFrecuenciaCompletaAttribute(): string
    {
        return "{$this->frecuencia} {$this->frecuencia_unidad}";
    }

    public function getParasitosListaAttribute(): string
    {
        $parasitos = $this->parasitos;
        
        if ($this->otros_parasitos && in_array('otros', $parasitos)) {
            // Reemplazar 'otros' por el valor específico
            $key = array_search('otros', $parasitos);
            if ($key !== false) {
                $parasitos[$key] = $this->otros_parasitos;
            }
        }
        
        return implode(', ', $parasitos);
    }

    /**
     * Scope para búsquedas
     */
    public function scopePorEspecie($query, $especie)
    {
        return $query->whereJsonContains('especies', $especie);
    }

    public function scopePorParasito($query, $parasito)
    {
        return $query->whereJsonContains('parasitos', $parasito);
    }

    public function scopePorVia($query, $via)
    {
        return $query->where('via_administracion', $via);
    }

    /**
     * Validar si aplica para una edad específica
     */
    public function aplicaParaEdad($edadEnSemanas): bool
    {
        $edadMinimaEnSemanas = $this->edad_unidad === 'meses' 
            ? $this->edad_minima * 4 
            : $this->edad_minima;
            
        return $edadEnSemanas >= $edadMinimaEnSemanas;
    }
}