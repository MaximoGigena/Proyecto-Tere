<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoDiagnostico extends Model
{
    use HasFactory, softDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipos_diagnostico';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'clasificacion',
        'clasificacion_otro',
        'especies',
        'evolucion',
        'criterios_diagnosticos',
        'tratamiento_sugerido',
        'riesgos_complicaciones',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Scope para filtrar por clasificación
     */
    public function scopePorClasificacion($query, $clasificacion)
    {
        return $query->where('clasificacion', $clasificacion);
    }

    /**
     * Scope para filtrar por especie
     */
    public function scopePorEspecie($query, $especie)
    {
        return $query->whereJsonContains('especies', $especie);
    }

    /**
     * Scope para filtrar por evolución
     */
    public function scopePorEvolucion($query, $evolucion)
    {
        return $query->where('evolucion', $evolucion);
    }

    /**
     * Scope para diagnósticos infecciosos
     */
    public function scopeInfecciosos($query)
    {
        return $query->where('clasificacion', 'infeccioso');
    }

    /**
     * Scope para diagnósticos crónicos
     */
    public function scopeCronicos($query)
    {
        return $query->where('evolucion', 'cronica');
    }

    /**
     * Scope para diagnósticos agudos
     */
    public function scopeAgudos($query)
    {
        return $query->where('evolucion', 'aguda');
    }

    /**
     * Scope para búsqueda por nombre
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%");
    }

    /**
     * Get clasificación final (si es "otro" usa clasificacion_otro)
     */
    public function getClasificacionFinalAttribute(): string
    {
        return $this->clasificacion === 'otro' && $this->clasificacion_otro 
            ? $this->clasificacion_otro 
            : $this->clasificacion;
    }

    /**
     * Get clasificación en texto legible
     */
    public function getClasificacionTextoAttribute(): string
    {
        return match($this->clasificacion) {
            'infeccioso' => 'Infeccioso',
            'genetico' => 'Genético',
            'nutricional' => 'Nutricional',
            'ambiental' => 'Ambiental',
            'traumatico' => 'Traumático',
            'degenerativo' => 'Degenerativo',
            'neoplasico' => 'Neoplásico',
            'otro' => $this->clasificacion_otro ?: 'Otro',
            default => $this->clasificacion,
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
     * Get evolución en texto legible
     */
    public function getEvolucionTextoAttribute(): string
    {
        return match($this->evolucion) {
            'aguda' => 'Aguda',
            'cronica' => 'Crónica',
            'recurrente' => 'Recurrente',
            'autolimitada' => 'Autolimitada',
            'progresiva' => 'Progresiva',
            default => $this->evolucion,
        };
    }

    /**
     * Check if diagnóstico es crónico
     */
    public function getEsCronicoAttribute(): bool
    {
        return $this->evolucion === 'cronica';
    }

    /**
     * Check if diagnóstico es agudo
     */
    public function getEsAgudoAttribute(): bool
    {
        return $this->evolucion === 'aguda';
    }

    /**
     * Check if diagnóstico es infeccioso
     */
    public function getEsInfecciosoAttribute(): bool
    {
        return $this->clasificacion === 'infeccioso';
    }

    /**
     * Check if diagnóstico es genético
     */
    public function getEsGeneticoAttribute(): bool
    {
        return $this->clasificacion === 'genetico';
    }

    /**
     * Check if diagnóstico es neoplásico
     */
    public function getEsNeoplasicoAttribute(): bool
    {
        return $this->clasificacion === 'neoplasico';
    }

    /**
     * Validación para crear un nuevo tipo de diagnóstico
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_diagnostico,nombre',
            'descripcion' => 'required|string',
            'clasificacion' => 'required|in:infeccioso,genetico,nutricional,ambiental,traumatico,degenerativo,neoplasico,otro',
            'clasificacion_otro' => 'nullable|required_if:clasificacion,otro|string|max:255',
            'especies' => 'required|array|min:1', // Cambiado a array
            'especies.*' => 'in:canino,felino,equino,bovino,ave,pez,otro', // Validación para cada elemento
            'evolucion' => 'required|in:aguda,cronica,recurrente,autolimitada,progresiva',
            'criterios_diagnosticos' => 'required|string',
            'tratamiento_sugerido' => 'nullable|string',
            'riesgos_complicaciones' => 'nullable|string',
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
            'nombre.required' => 'El nombre del diagnóstico es obligatorio.',
            'nombre.unique' => 'Ya existe un diagnóstico con este nombre.',
            'descripcion.required' => 'La descripción general es obligatoria.',
            'clasificacion.required' => 'La clasificación es obligatoria.',
            'clasificacion_otro.required_if' => 'Debe especificar la clasificación cuando selecciona "Otro".',
            'especies.required' => 'Debe seleccionar al menos una especie objetivo.', // Mensaje actualizado
            'especies.min' => 'Debe seleccionar al menos una especie objetivo.',
            'evolucion.required' => 'La evolución típica es obligatoria.',
            'criterios_diagnosticos.required' => 'Los criterios diagnósticos son obligatorios.',
        ];
    }

    /**
     * Get descripción resumida del diagnóstico
     */
    public function getDescripcionResumidaAttribute(): string
    {
        return "{$this->nombre} - {$this->clasificacion_texto} ({$this->evolucion_texto})";
    }

    /**
     * Check if diagnóstico aplica para todas las especies
     */
    public function getAplicaATodasEspeciesAttribute(): bool
    {
        return in_array('todos', $this->especies ?? []);
    }

    /**
     * Check if diagnóstico no tiene especie específica
     */
    public function getSinEspecieEspecificaAttribute(): bool
    {
        return in_array('ninguna', $this->especies ?? []);
    }
}