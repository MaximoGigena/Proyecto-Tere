<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoPaliativo;
use App\Models\FarmacoAsociado;
use App\Models\ProcesoMedico;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuidadoPaliativo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuidados_paliativos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_paliativo_id',
        'fecha_inicio',
        'diagnostico_base',
        'resultado',
        'estado_mascota',
        'frecuencia_valor',
        'frecuencia_unidad',
        'medicacion_complementaria',
        'recomendaciones_tutor',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_inicio' => 'datetime',
        'frecuencia_valor' => 'integer',
        'resultado' => 'string',
        'estado_mascota' => 'string',
        'frecuencia_unidad' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtener el tipo de procedimiento paliativo asociado.
     */
    public function tipoPaliativo()
    {
        return $this->belongsTo(TipoPaliativo::class);
    }

    /**
     * Obtener los valores permitidos para el campo 'resultado'.
     */
    public static function getResultadosPermitidos()
    {
        return ['mejoria', 'alivio', 'estabilizacion', 'sin_cambio', 'empeoramiento'];
    }

    /**
     * Obtener los valores permitidos para el campo 'estado_mascota'.
     */
    public static function getEstadosMascotaPermitidos()
    {
        return ['estable', 'dolor_controlado', 'dolor_parcial', 'deterioro', 'critico'];
    }

    /**
     * Obtener los valores permitidos para el campo 'frecuencia_unidad'.
     */
    public static function getFrecuenciasUnidadPermitidas()
    {
        return ['horas', 'dias', 'semanas', 'meses'];
    }

    /**
     * Obtener los valores para un dropdown de resultados.
     */
    public static function getResultadosDropdown()
    {
        return [
            'mejoria' => 'Mejoría',
            'alivio' => 'Alivio',
            'estabilizacion' => 'Estabilización',
            'sin_cambio' => 'Sin cambio',
            'empeoramiento' => 'Empeoramiento',
        ];
    }

    /**
     * Obtener los valores para un dropdown de estados de mascota.
     */
    public static function getEstadosMascotaDropdown()
    {
        return [
            'estable' => 'Estable',
            'dolor_controlado' => 'Dolor controlado',
            'dolor_parcial' => 'Dolor parcialmente controlado',
            'deterioro' => 'Deterioro',
            'critico' => 'Crítico',
        ];
    }

    /**
     * Scope para filtrar por tipo de paliativo.
     */
    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_paliativo_id', $tipoId);
    }

    /**
     * Scope para filtrar por resultado.
     */
    public function scopePorResultado($query, $resultado)
    {
        return $query->where('resultado', $resultado);
    }

    /**
     * Scope para filtrar por estado de mascota.
     */
    public function scopePorEstadoMascota($query, $estado)
    {
        return $query->where('estado_mascota', $estado);
    }

    /**
     * Scope para cuidados paliativos activos (sin fecha fin).
     */
    public function scopeActivos($query)
    {
        return $query->where('estado_mascota', '!=', 'critico')
                    ->where(function ($q) {
                        $q->whereNull('fecha_fin')
                          ->orWhere('fecha_fin', '>', now());
                    });
    }

    /**
     * Scope para cuidados paliativos críticos.
     */
    public function scopeCriticos($query)
    {
        return $query->where('estado_mascota', 'critico');
    }

    /**
     * Scope para buscar por diagnóstico base.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('diagnostico_base', 'like', "%{$termino}%")
                    ->orWhere('observaciones', 'like', "%{$termino}%")
                    ->orWhere('medicacion_complementaria', 'like', "%{$termino}%");
    }

    /**
     * Calcular la frecuencia completa (valor + unidad).
     */
    public function getFrecuenciaCompletaAttribute()
    {
        if ($this->frecuencia_valor && $this->frecuencia_unidad) {
            $unidades = [
                'horas' => 'hora(s)',
                'dias' => 'día(s)',
                'semanas' => 'semana(s)',
                'meses' => 'mes(es)',
            ];
            
            $unidad = $unidades[$this->frecuencia_unidad] ?? $this->frecuencia_unidad;
            return "Cada {$this->frecuencia_valor} {$unidad}";
        }
        
        return null;
    }

    /**
     * Verificar si el cuidado paliativo requiere seguimiento frecuente.
     */
    public function requiereSeguimientoFrecuente()
    {
        return $this->frecuencia_unidad === 'horas' || 
               ($this->frecuencia_unidad === 'dias' && $this->frecuencia_valor <= 3);
    }

    /**
     * Calcular la próxima aplicación basada en la frecuencia.
     */
    public function calcularProximaAplicacion()
    {
        if (!$this->fecha_inicio || !$this->frecuencia_valor || !$this->frecuencia_unidad) {
            return null;
        }

        $fecha = $this->fecha_inicio->copy();
        
        switch ($this->frecuencia_unidad) {
            case 'horas':
                return $fecha->addHours($this->frecuencia_valor);
            case 'dias':
                return $fecha->addDays($this->frecuencia_valor);
            case 'semanas':
                return $fecha->addWeeks($this->frecuencia_valor);
            case 'meses':
                return $fecha->addMonths($this->frecuencia_valor);
            default:
                return null;
        }
    }

    /**
     * Formatear la fecha de inicio para mostrar.
     */
    public function getFechaInicioFormateadaAttribute()
    {
        return $this->fecha_inicio->format('d/m/Y H:i');
    }

    /**
     * Obtener la medicación complementaria como array.
     */
    public function getMedicacionComplementariaArrayAttribute()
    {
        if (empty($this->medicacion_complementaria)) {
            return [];
        }

        return explode("\n", $this->medicacion_complementaria);
    }

    /**
     * Fármacos asociados al cuidado paliativo
     */
    public function farmacosAsociados()
    {
        return $this->morphMany(FarmacoAsociado::class, 'farmacable');
    }
    
    /**
     * Agregar un fármaco al cuidado paliativo
     */
    public function agregarFarmaco(array $datos)
    {
        return $this->farmacosAsociados()->create(array_merge(
            $datos,
            ['farmacable_type' => self::class]
        ));
    }
    
    /**
     * Obtener fármacos por momento de aplicación
     */
    public function farmacosPorMomento($momento)
    {
        return $this->farmacosAsociados()
                    ->with('tipoFarmaco')
                    ->where('momento_aplicacion', $momento)
                    ->get();
    }

    public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }
    
    /**
     * Obtener los diagnósticos asociados al cuidado paliativo
     */
    public function diagnosticosAsociados()
    {
        return $this->hasMany(\App\Models\ProcedimientoDiagnostico::class, 'procedimiento_id')
                    ->where('procedimiento_type', self::class);
    }

    /**
     * Obtener los tipos de diagnóstico a través de la tabla pivote
     */
    public function diagnosticos()
    {
        return $this->belongsToMany(
            \App\Models\TiposProcedimientos\TipoDiagnostico::class,
            'procedimiento_diagnostico',
            'procedimiento_id',
            'diagnostico_id'
        )->wherePivot('diagnostico_type', 'App\\Models\\TiposProcedimientos\\TipoDiagnostico')
        ->wherePivot('procedimiento_type', self::class)
        ->withPivot(['estado', 'relevancia', 'observaciones', 'created_at'])
        ->withTimestamps();
    }
}