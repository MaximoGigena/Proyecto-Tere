<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\TiposProcedimientos\TipoFarmaco;
use App\Models\ProcedimientosMedicos\Cirugia;
use App\Models\ProcedimientosMedicos\CuidadoPaliativo;

class FarmacoAsociado extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'farmacos_asociados';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_farmaco_id',
        'farmacable_type',
        'farmacable_id',
        'dosis_prescrita',
        'unidad_dosis',
        'es_dosis_unica',
        'frecuencia_valor',
        'frecuencia_unidad',
        'duracion_valor',
        'duracion_unidad',
        'etapa_aplicacion',
        'momento_aplicacion',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dosis_prescrita' => 'decimal:3',
        'es_dosis_unica' => 'boolean',
        'frecuencia_valor' => 'integer',
        'duracion_valor' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el tipo de fármaco (catálogo)
     */
    public function tipoFarmaco()
    {
        return $this->belongsTo(TipoFarmaco::class, 'tipo_farmaco_id');
    }

    /**
     * Relación polimórfica: puede ser Cirugia o CuidadoPaliativo
     */
    public function farmacable()
    {
        return $this->morphTo();
    }

    /**
     * Obtener la descripción de la frecuencia
     */
    public function getFrecuenciaCompletaAttribute(): ?string
    {
        if ($this->es_dosis_unica || !$this->frecuencia_valor) {
            return null;
        }

        $unidadTexto = match($this->frecuencia_unidad) {
            'h' => 'horas',
            'd' => 'días',
            default => $this->frecuencia_unidad,
        };

        return "Cada {$this->frecuencia_valor} {$unidadTexto}";
    }

    /**
     * Obtener la descripción de la duración
     */
    public function getDuracionCompletaAttribute(): ?string
    {
        if ($this->es_dosis_unica || !$this->duracion_valor) {
            return null;
        }

        $unidadTexto = match($this->duracion_unidad) {
            'min' => 'minutos',
            'h' => 'horas',
            'd' => 'días',
            default => $this->duracion_unidad,
        };

        return "Durante {$this->duracion_valor} {$unidadTexto}";
    }

    /**
     * Obtener texto de la etapa de aplicación (para cirugías)
     */
    public function getEtapaAplicacionTextoAttribute(): ?string
    {
        return match($this->etapa_aplicacion) {
            'prequirurgica' => 'Prequirúrgica',
            'transquirurgica' => 'Transquirúrgica',
            'postquirurgica_inmediata' => 'Postquirúrgica inmediata',
            'postquirurgica_tardia' => 'Postquirúrgica tardía',
            default => null,
        };
    }

    /**
     * Obtener abreviatura de la etapa
     */
    public function getEtapaAplicacionAbreviadaAttribute(): ?string
    {
        return match($this->etapa_aplicacion) {
            'prequirurgica' => 'Pre',
            'transquirurgica' => 'Trans',
            'postquirurgica_inmediata' => 'Post Inm',
            'postquirurgica_tardia' => 'Post Tar',
            default => null,
        };
    }

    /**
     * Obtener texto del momento de aplicación (para cuidados paliativos)
     */
    public function getMomentoAplicacionTextoAttribute(): ?string
    {
        return match($this->momento_aplicacion) {
            'inicio' => 'Inicio',
            'mantenimiento' => 'Mantenimiento',
            'rescue' => 'Rescate',
            'final' => 'Final',
            default => null,
        };
    }

    /**
     * Obtener dosis formateada
     */
    public function getDosisFormateadaAttribute(): string
    {
        return "{$this->dosis_prescrita} {$this->unidad_dosis}";
    }

    /**
     * Determinar si es para una cirugía
     */
    public function esCirugia(): bool
    {
        return $this->farmacable_type === Cirugia::class;
    }

    /**
     * Determinar si es para un cuidado paliativo
     */
    public function esCuidadoPaliativo(): bool
    {
        return $this->farmacable_type === CuidadoPaliativo::class;
    }

    /**
     * Obtener el contexto apropiado (etapa o momento)
     */
    public function getContextoAplicacionAttribute(): ?string
    {
        if ($this->esCirugia()) {
            return $this->etapa_aplicacion_texto;
        } elseif ($this->esCuidadoPaliativo()) {
            return $this->momento_aplicacion_texto;
        }
        
        return null;
    }

    /**
     * Validación para asociar un fármaco
     */
    public static function rules(): array
    {
        return [
            'tipo_farmaco_id' => 'required|exists:tipos_farmaco,id',
            'farmacable_type' => 'required|in:' . Cirugia::class . ',' . CuidadoPaliativo::class,
            'farmacable_id' => 'required|integer',
            'dosis_prescrita' => 'required|numeric|min:0.001',
            'unidad_dosis' => 'required|string|max:20',
            'es_dosis_unica' => 'boolean',
            'frecuencia_valor' => 'nullable|required_if:es_dosis_unica,false|integer|min:1',
            'frecuencia_unidad' => 'nullable|required_if:es_dosis_unica,false|in:h,d',
            'duracion_valor' => 'nullable|required_if:es_dosis_unica,false|integer|min:1',
            'duracion_unidad' => 'nullable|required_if:es_dosis_unica,false|in:min,h,d',
            'etapa_aplicacion' => 'nullable|required_if:farmacable_type,' . Cirugia::class . '|in:prequirurgica,transquirurgica,postquirurgica_inmediata,postquirurgica_tardia',
            'momento_aplicacion' => 'nullable|required_if:farmacable_type,' . CuidadoPaliativo::class . '|in:inicio,mantenimiento,rescue,final',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Scope para fármacos de cirugías
     */
    public function scopeParaCirugias($query)
    {
        return $query->where('farmacable_type', Cirugia::class);
    }

    /**
     * Scope para fármacos de cuidados paliativos
     */
    public function scopeParaCuidadosPaliativos($query)
    {
        return $query->where('farmacable_type', CuidadoPaliativo::class);
    }

    /**
     * Scope para fármacos por etapa
     */
    public function scopePorEtapa($query, $etapa)
    {
        return $query->where('etapa_aplicacion', $etapa);
    }

    /**
     * Scope para fármacos por momento
     */
    public function scopePorMomento($query, $momento)
    {
        return $query->where('momento_aplicacion', $momento);
    }

    /**
     * Scope para dosis únicas
     */
    public function scopeDosisUnicas($query)
    {
        return $query->where('es_dosis_unica', true);
    }

    /**
     * Scope para tratamientos prolongados
     */
    public function scopeTratamientosProlongados($query)
    {
        return $query->where('es_dosis_unica', false);
    }
}