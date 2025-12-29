<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProcesoMedico;
use App\Models\TiposProcedimientos\TipoAlergia;

class Alergia extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_alergia_id',
        'fecha_deteccion',
        'gravedad',
        'reaccion_comun',
        'estado',
        'desencadenante',
        'conducta_recomendada',
        'recomendaciones_tutor',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_deteccion' => 'date',
    ];

    /**
     * Get the tipo_alergia that owns the Alergia
     */
    public function tipoAlergia()
    {
        return $this->belongsTo(TipoAlergia::class);
    }

    /**
     * Get the proceso medico associated with this alergia.
     */
    public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }
}