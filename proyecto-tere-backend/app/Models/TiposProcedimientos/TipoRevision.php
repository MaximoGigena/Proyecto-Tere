<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoRevision extends Model
{
    use HasFactory, softDeletes;

    protected $table = 'tipos_revision';

    protected $fillable = [
        'nombre',
        'descripcion',
        'frecuencia_recomendada',
        'frecuencia_personalizada',
        'areas_revisar',
        'otra_area',
        'indicadores_clave',
        'especie_objetivo',
        'edad_sugerida',
        'edad_unidad',
        'recomendaciones_profesionales',
        'riesgos_clinicos',
        'veterinario_id',
        'activo'
    ];

    protected $casts = [
        'areas_revisar' => 'array',
        'edad_sugerida' => 'decimal:2',
        'activo' => 'boolean'
    ];

    // Relación con Veterinario
    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Accesores para facilitar el uso en las vistas
     */
    public function getFrecuenciaFinalAttribute(): string
    {
        return $this->frecuencia_recomendada === 'personalizada' 
            ? $this->frecuencia_personalizada 
            : $this->frecuencia_recomendada;
    }

    public function getAreasRevisarCompletoAttribute(): string
    {
        $areas = $this->areas_revisar;
        
        if ($this->otra_area) {
            $areas[] = $this->otra_area;
        }
        
        return implode(', ', $areas);
    }

    public function getEdadCompletaAttribute(): ?string
    {
        if (!$this->edad_sugerida) {
            return null;
        }
        
        return "{$this->edad_sugerida} {$this->edad_unidad}";
    }

    /**
     * Convertir edad a semanas para comparaciones
     */
    public function getEdadEnSemanasAttribute(): ?float
    {
        if (!$this->edad_sugerida) {
            return null;
        }

        return match($this->edad_unidad) {
            'semanas' => $this->edad_sugerida,
            'meses' => $this->edad_sugerida * 4,
            'años' => $this->edad_sugerida * 52,
            default => null
        };
    }

    /**
     * Scopes para búsquedas
     */
    public function scopePorEspecie($query, $especie)
    {
        if ($especie === 'todos') {
            return $query;
        }
        
        return $query->where('especie_objetivo', $especie)
                    ->orWhere('especie_objetivo', 'todos');
    }

    public function scopePorFrecuencia($query, $frecuencia)
    {
        return $query->where('frecuencia_recomendada', $frecuencia);
    }

    public function scopePorArea($query, $area)
    {
        return $query->whereJsonContains('areas_revisar', $area);
    }

    /**
     * Validar si aplica para una edad específica (en semanas)
     */
    public function aplicaParaEdad($edadEnSemanas): bool
    {
        if (!$this->edad_sugerida) {
            return true; // Sin restricción de edad
        }

        $edadSugeridaEnSemanas = $this->edad_en_semanas;
        return $edadEnSemanas >= $edadSugeridaEnSemanas;
    }

    /**
     * Obtener áreas de revisión predefinidas (como en el frontend)
     */
    public static function getAreasPredefinidas(): array
    {
        return [
            ['value' => 'piel', 'label' => 'Piel'],
            ['value' => 'ojos', 'label' => 'Ojos'],
            ['value' => 'oidos', 'label' => 'Oídos'],
            ['value' => 'boca', 'label' => 'Boca'],
            ['value' => 'corazon', 'label' => 'Corazón'],
            ['value' => 'pulmones', 'label' => 'Pulmones'],
            ['value' => 'abdomen', 'label' => 'Abdomen'],
            ['value' => 'articulaciones', 'label' => 'Articulaciones'],
            ['value' => 'comportamiento', 'label' => 'Comportamiento'],
            ['value' => 'nutricion', 'label' => 'Nutrición']
        ];
    }
}