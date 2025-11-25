<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoDesparasitacion;
use App\Models\ProcesoMedico;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Desparasitacion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'desparasitaciones';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_desparasitacion_id',
        'fecha',
        'nombre_producto',
        'dosis',
        'frecuencia_valor',
        'frecuencia_unidad',
        'peso',
        'proxima_fecha',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'proxima_fecha' => 'date',
        'peso' => 'decimal:2',
        'frecuencia_valor' => 'integer',
    ];

    /**
     * Get the tipo_desparasitacion that owns the Desparasitacion
     */
    public function tipoDesparasitacion()
    {
        return $this->belongsTo(TipoDesparasitacion::class, 'tipo_desparasitacion_id');
    }

    /**
     * Relación polimórfica con ProcesoMedico
     * Una vacuna tiene un proceso médico asociado
     */
    public function procesoMedico(): MorphOne
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }

    /**
     * Accessor para obtener el nombre del tipo de desparasitación
     */
    public function getNombreTipoAttribute(): string
    {
        return $this->tipo->nombre ?? 'Tipo no especificado';
    }
}