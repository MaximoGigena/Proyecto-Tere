<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoCirugia extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipos_cirugia';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'especie',
        'frecuencia',
        'duracion',
        'duracion_unidad',
        'riesgos',
        'recomendaciones_preoperatorias',
        'recomendaciones_postoperatorias',
        'requerimientos_anestesia',
        'equipamiento',
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
        'equipamiento' => 'array',
        'duracion' => 'integer',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'equipamiento' => 'array',
            'duracion' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

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
     * Get duración completa formateada
     */
    public function getDuracionCompletaAttribute(): string
    {
        return "{$this->duracion} {$this->duracion_unidad}";
    }

    /**
     * Get equipamiento como array
     */
    public function getEquipamientoListaAttribute(): ?string
    {
        if (empty($this->equipamiento)) {
            return null;
        }

        return implode('; ', $this->equipamiento);
    }

    /**
     * Set equipamiento desde string
     */
    public function setEquipamientoAttribute($value): void
    {
        if (is_string($value)) {
            $equipamiento = array_map('trim', explode(';', $value));
            $equipamiento = array_filter($equipamiento);
            $this->attributes['equipamiento'] = json_encode($equipamiento);
        } else {
            $this->attributes['equipamiento'] = json_encode($value);
        }
    }

    /**
     * Validación para crear un nuevo tipo de cirugía
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_cirugia,nombre',
            'descripcion' => 'required|string',
            'especie' => 'required|in:canino,felino,ave,roedor,exotico,todos',
            'frecuencia' => 'required|in:unica,potencial_repetible,multiple',
            'duracion' => 'required|integer|min:1',
            'duracion_unidad' => 'required|in:minutos,horas',
            'riesgos' => 'required|string',
            'recomendaciones_preoperatorias' => 'required|string',
            'recomendaciones_postoperatorias' => 'nullable|string',
            'requerimientos_anestesia' => 'nullable|string',
            'equipamiento' => 'nullable|array',
            'equipamiento.*' => 'string|max:255',
            'observaciones' => 'nullable|string',
        ];
    }

    /**
     * Mensajes de validación
     */
    public static function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de cirugía es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de cirugía con este nombre.',
            'descripcion.required' => 'La descripción general es obligatoria.',
            'especie.required' => 'La especie objetivo es obligatoria.',
            'frecuencia.required' => 'La frecuencia esperada es obligatoria.',
            'duracion.required' => 'La duración estimada es obligatoria.',
            'riesgos.required' => 'Los riesgos comunes son obligatorios.',
            'recomendaciones_preoperatorias.required' => 'Las recomendaciones preoperatorias son obligatorias.',
        ];
    }
}