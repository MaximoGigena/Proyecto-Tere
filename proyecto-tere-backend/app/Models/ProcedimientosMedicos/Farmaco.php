<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProcesoMedico;
use App\Models\TiposProcedimientos\TipoFarmaco;

class Farmaco extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_farmaco_id',
        'fecha_administracion',
        'frecuencia',
        'duracion_tratamiento',
        'dosis',
        'unidad_dosis',
        'proxima_dosis',
        'reacciones_adversas',
        'recomendaciones_tutor',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_administracion' => 'datetime',
        'proxima_dosis' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtener el tipo de fármaco asociado.
     */
    public function tipoFarmaco()
    {
        return $this->belongsTo(TipoFarmaco::class);
    }

    /**
     * Obtener los valores permitidos para el campo 'unidad_dosis'.
     */
    public static function getUnidadesDosisPermitidas()
    {
        return ['mg', 'ml', 'UI', 'comp', 'gotas'];
    }

    /**
     * Scope para filtrar por tipo de fármaco.
     */
    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_farmaco_id', $tipoId);
    }

    /**
     * Scope para fármacos que requieren próxima dosis.
     */
    public function scopeRequiereProximaDosis($query)
    {
        return $query->whereNotNull('proxima_dosis');
    }

    /**
     * Scope para fármacos con reacciones adversas.
     */
    public function scopeConReaccionesAdversas($query)
    {
        return $query->whereNotNull('reacciones_adversas');
    }

    /**
     * Scope para obtener solo fármacos activos (no eliminados).
     */
    public function scopeActivos($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope para obtener fármacos eliminados.
     */
    public function scopeEliminados($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    /**
     * Calcular la próxima dosis basada en la frecuencia.
     */
    public function calcularProximaDosis()
    {
        if (!$this->fecha_administracion || !$this->frecuencia) {
            return null;
        }

        $intervalos = [
            'Cada 8 h' => 8,
            'Cada 12 h' => 12,
            '1 vez al día' => 24,
            '2 veces al día' => 12,
            '3 veces al día' => 8,
        ];

        if (isset($intervalos[$this->frecuencia])) {
            $horas = $intervalos[$this->frecuencia];
            return $this->fecha_administracion->copy()->addHours($horas);
        }

        return null;
    }

    /**
     * Obtener el proceso médico asociado.
     */
    public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }
}