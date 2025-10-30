<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoTerapia extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipos_terapia';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'especie',
        'duracion_valor',
        'duracion_unidad',
        'frecuencia',
        'requisitos',
        'indicaciones_clinicas',
        'contraindicaciones',
        'riesgos_efectos_secundarios',
        'recomendaciones_clinicas',
        'observaciones',
        'veterinario_id',
        'activo'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duracion_valor' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'activo' => 'boolean'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duracion_valor' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relación con Veterinario
    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Scope para filtrar por especie
     */
    public function scopePorEspecie($query, $especie)
    {
        return $query->where('especie', $especie)
                    ->orWhere('especie', 'todos');
    }

    /**
     * Scope para filtrar por frecuencia
     */
    public function scopePorFrecuencia($query, $frecuencia)
    {
        return $query->where('frecuencia', $frecuencia);
    }

    /**
     * Scope para terapias de duración corta (sesiones o días)
     */
    public function scopeDuracionCorta($query)
    {
        return $query->whereIn('duracion_unidad', ['sesiones', 'dias']);
    }

    /**
     * Scope para terapias de duración larga (semanas o meses)
     */
    public function scopeDuracionLarga($query)
    {
        return $query->whereIn('duracion_unidad', ['semanas', 'meses']);
    }

    /**
     * Get duración completa formateada
     */
    public function getDuracionCompletaAttribute(): string
    {
        return "{$this->duracion_valor} {$this->duracion_unidad}";
    }

    /**
     * Get duración en días estimados (para cálculos)
     */
    public function getDuracionEnDiasAttribute(): ?int
    {
        return match($this->duracion_unidad) {
            'sesiones' => $this->duracion_valor, // Asumiendo 1 sesión por día
            'dias' => $this->duracion_valor,
            'semanas' => $this->duracion_valor * 7,
            'meses' => $this->duracion_valor * 30, // Aproximación
            default => null,
        };
    }

    /**
     * Validación para crear un nuevo tipo de terapia
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_terapia,nombre',
            'descripcion' => 'required|string',
            'especie' => 'required|in:canino,felino,ave,roedor,exotico,todos',
            'duracion_valor' => 'required|integer|min:1',
            'duracion_unidad' => 'required|in:sesiones,dias,semanas,meses',
            'frecuencia' => 'required|in:diaria,semanal,quincenal,mensual,personalizada',
            'requisitos' => 'required|string',
            'indicaciones_clinicas' => 'required|string',
            'contraindicaciones' => 'nullable|string',
            'riesgos_efectos_secundarios' => 'nullable|string',
            'recomendaciones_clinicas' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ];
    }

    /**
     * Mensajes de validación
     */
    public static function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de terapia es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de terapia con este nombre.',
            'descripcion.required' => 'La descripción general es obligatoria.',
            'especie.required' => 'La especie objetivo es obligatoria.',
            'duracion_valor.required' => 'La duración del tratamiento es obligatoria.',
            'duracion_valor.min' => 'La duración debe ser al menos 1.',
            'frecuencia.required' => 'La frecuencia sugerida es obligatoria.',
            'requisitos.required' => 'Los requisitos o condiciones previas son obligatorios.',
            'indicaciones_clinicas.required' => 'Las indicaciones clínicas son obligatorias.',
        ];
    }

    /**
     * Check if terapia es personalizada
     */
    public function getEsPersonalizadaAttribute(): bool
    {
        return $this->frecuencia === 'personalizada';
    }

    /**
     * Get frecuencia en texto legible
     */
    public function getFrecuenciaTextoAttribute(): string
    {
        return match($this->frecuencia) {
            'diaria' => 'Diaria',
            'semanal' => 'Semanal',
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
            'personalizada' => 'Personalizada',
            default => $this->frecuencia,
        };
    }

    /**
     * Get unidad de duración en texto legible
     */
    public function getDuracionUnidadTextoAttribute(): string
    {
        return match($this->duracion_unidad) {
            'sesiones' => 'sesiones',
            'dias' => 'días',
            'semanas' => 'semanas',
            'meses' => 'meses',
            default => $this->duracion_unidad,
        };
    }
}