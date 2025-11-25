<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Veterinario;

class TipoPaliativo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipos_paliativo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'especies',
        'objetivo_terapeutico',
        'objetivo_otro',
        'frecuencia_valor',
        'frecuencia_unidad',
        'indicaciones_clinicas',
        'contraindicaciones',
        'riesgos_efectos_secundarios',
        'recursos_necesarios',
        'recomendaciones_clinicas',
        'observaciones',
        'veterinario_id',
        'activo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'especies' => 'array',
        'frecuencia_valor' => 'integer',
        'recursos_necesarios' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'especies' => 'array',
            'frecuencia_valor' => 'integer',
            'recursos_necesarios' => 'array',
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
        return $query->whereJsonContains('especies', $especie);
    }

    /**
     * Scope para filtrar por objetivo terapéutico
     */
    public function scopePorObjetivo($query, $objetivo)
    {
        return $query->where('objetivo_terapeutico', $objetivo);
    }

    /**
     * Scope para procedimientos de corta duración (horas, días)
     */
    public function scopeDuracionCorta($query)
    {
        return $query->whereIn('frecuencia_unidad', ['horas', 'dias']);
    }

    /**
     * Scope para procedimientos de larga duración (semanas, meses)
     */
    public function scopeDuracionLarga($query)
    {
        return $query->whereIn('frecuencia_unidad', ['semanas', 'meses']);
    }

    /**
     * Get frecuencia completa formateada
     */
    public function getFrecuenciaCompletaAttribute(): string
    {
        return "{$this->frecuencia_valor} {$this->frecuencia_unidad}";
    }

    /**
     * Get objetivo terapéutico final (si es "otro" usa objetivo_otro)
     */
    public function getObjetivoTerapeuticoFinalAttribute(): string
    {
        return $this->objetivo_terapeutico === 'otro' && $this->objetivo_otro 
            ? $this->objetivo_otro 
            : $this->objetivo_terapeutico;
    }

    /**
     * Get recursos como string separado por punto y coma
     */
    public function getRecursosListaAttribute(): ?string
    {
        if (empty($this->recursos_necesarios)) {
            return null;
        }

        return implode('; ', $this->recursos_necesarios);
    }

    /**
     * Set recursos desde string
     */
    public function setRecursosNecesariosAttribute($value): void
    {
        if (is_string($value)) {
            $recursos = array_map('trim', explode(';', $value));
            $recursos = array_filter($recursos);
            $this->attributes['recursos_necesarios'] = json_encode($recursos);
        } else {
            $this->attributes['recursos_necesarios'] = json_encode($value);
        }
    }

    /**
     * Get objetivo terapéutico en texto legible
     */
    public function getObjetivoTextoLarAttribute(): string
    {
        return match($this->objetivo_terapeutico) {
            'alivio_dolor' => 'Alivio del dolor',
            'mejora_movilidad' => 'Mejora de movilidad',
            'soporte_respiratorio' => 'Soporte respiratorio',
            'soporte_nutricional' => 'Soporte nutricional',
            'acompañamiento' => 'Acompañamiento final',
            'otro' => $this->objetivo_otro ?: 'Otro objetivo',
            default => $this->objetivo_terapeutico,
        };
    }

    /**
     * Get especie en texto legible
     */
    public function getEspeciesTextoAttribute(): string
    {
        if (empty($this->especies)) {
            return 'No especificado';
        }

        $nombres = array_map(function($especie) {
            return match($especie) {
                'canino' => 'Canino',
                'felino' => 'Felino',
                'equino' => 'Equino',
                'bovino' => 'Bovino',
                'ave' => 'Ave',
                'pez' => 'Pez',
                'otro' => 'Otro',
                default => $especie,
            };
        }, $this->especies);

        return implode(', ', $nombres);
    }

    /**
     * Get unidad de frecuencia en texto legible
     */
    public function getFrecuenciaUnidadTextoAttribute(): string
    {
        return match($this->frecuencia_unidad) {
            'horas' => 'horas',
            'dias' => 'días',
            'semanas' => 'semanas',
            'meses' => 'meses',
            'sesiones' => 'sesiones',
            default => $this->frecuencia_unidad,
        };
    }

    /**
     * Check if es procedimiento de acompañamiento
     */
    public function getEsAcompanamientoAttribute(): bool
    {
        return $this->objetivo_terapeutico === 'acompañamiento';
    }

    /**
     * Check if es procedimiento de alivio del dolor
     */
    public function getEsAlivioDolorAttribute(): bool
    {
        return $this->objetivo_terapeutico === 'alivio_dolor';
    }

    /**
     * Validación para crear un nuevo procedimiento paliativo
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_paliativo,nombre',
            'descripcion' => 'required|string',
            'especies' => 'required|array|min:1', // Cambiado a array
            'especies.*' => 'in:canino,felino,equino,bovino,ave,pez,otro', // Validación para cada elemento
            'objetivo_terapeutico' => 'required|in:alivio_dolor,mejora_movilidad,soporte_respiratorio,soporte_nutricional,acompañamiento,otro',
            'objetivo_otro' => 'nullable|required_if:objetivo_terapeutico,otro|string|max:255',
            'frecuencia_valor' => 'required|integer|min:1',
            'frecuencia_unidad' => 'required|in:horas,dias,semanas,meses,sesiones',
            'indicaciones_clinicas' => 'required|string',
            'contraindicaciones' => 'nullable|string',
            'riesgos_efectos_secundarios' => 'nullable|string',
            'recursos_necesarios' => 'nullable|array',
            'recursos_necesarios.*' => 'string|max:255',
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
            'nombre.required' => 'El nombre del procedimiento paliativo es obligatorio.',
            'nombre.unique' => 'Ya existe un procedimiento paliativo con este nombre.',
            'descripcion.required' => 'La descripción general es obligatoria.',
            'especies.required' => 'Debe seleccionar al menos una especie objetivo.', // Mensaje actualizado
            'especies.min' => 'Debe seleccionar al menos una especie objetivo.',
            'objetivo_terapeutico.required' => 'El objetivo terapéutico es obligatorio.',
            'objetivo_otro.required_if' => 'Debe especificar el objetivo cuando selecciona "Otro".',
            'frecuencia_valor.required' => 'La frecuencia o duración es obligatoria.',
            'frecuencia_valor.min' => 'La frecuencia debe ser al menos 1.',
            'indicaciones_clinicas.required' => 'Las indicaciones clínicas son obligatorias.',
        ];
    }
}