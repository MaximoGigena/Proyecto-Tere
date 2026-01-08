<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoCirugia;
use App\Models\ArchivoCirugia;
use App\Models\ProcesoMedico;
use App\Models\FarmacoAsociado;

class Cirugia extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_cirugia_id',
        'fecha_cirugia',
        'resultado',
        'estado_actual',
        'diagnostico_causa',
        'fecha_control_estimada',
        'descripcion_procedimiento',
        'medicacion_postquirurgica',
        'recomendaciones_tutor',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_cirugia' => 'datetime',
        'fecha_control_estimada' => 'date',
        'resultado' => 'string',
        'estado_actual' => 'string',
    ];

    /**
     * Obtener el tipo de cirugía asociado.
     */
    public function tipoCirugia()
    {
        return $this->belongsTo(TipoCirugia::class);
    }

    /**
     * Obtener los valores permitidos para el campo 'resultado'.
     */
    public static function getResultadosPermitidos()
    {
        return ['satisfactorio', 'complicaciones', 'estable', 'critico'];
    }

    /**
     * Obtener los valores permitidos para el campo 'estado_actual'.
     */
    public static function getEstadosPermitidos()
    {
        return ['recuperacion', 'alta', 'seguimiento', 'hospitalizado'];
    }

    /**
     * Scope para filtrar por resultado.
     */
    public function scopePorResultado($query, $resultado)
    {
        return $query->where('resultado', $resultado);
    }

    /**
     * Scope para filtrar por estado actual.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado_actual', $estado);
    }

    /**
     * Scope para cirugías programadas después de una fecha.
     */
    public function scopeDespuesDe($query, $fecha)
    {
        return $query->where('fecha_cirugia', '>', $fecha);
    }

    /**
     * Scope para cirugías programadas antes de una fecha.
     */
    public function scopeAntesDe($query, $fecha)
    {
        return $query->where('fecha_cirugia', '<', $fecha);
    }

    public function archivos()
    {
        return $this->hasMany(ArchivoCirugia::class);
    }

    public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }

    /**
     * Fármacos asociados a la cirugía
     */
    public function farmacosAsociados()
    {
        return $this->morphMany(FarmacoAsociado::class, 'farmacable');
    }
    
    /**
     * Agregar un fármaco a la cirugía
     */
    public function agregarFarmaco(array $datos)
    {
        return $this->farmacosAsociados()->create(array_merge(
            $datos,
            ['farmacable_type' => self::class]
        ));
    }
    
    /**
     * Obtener fármacos por etapa
     */
    public function farmacosPorEtapa($etapa)
    {
        return $this->farmacosAsociados()
                    ->with('tipoFarmaco')
                    ->where('etapa_aplicacion', $etapa)
                    ->get();
    }
}