<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProcesoMedico;
use App\Models\TiposProcedimientos\TipoAlergia;
use Carbon\Carbon;

class Alergia extends Model
{
    use HasFactory, SoftDeletes;

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
        'deleted_at', // Agregar este campo
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_deteccion' => 'date',
        'deleted_at' => 'datetime',
    ];

    /**
     * Mutador para fecha_deteccion
     * Asegura que siempre se guarde como fecha válida
     */
    public function setFechaDeteccionAttribute($value)
    {
        $this->attributes['fecha_deteccion'] = $value ? Carbon::parse($value) : null;
    }

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

    /**
     * Scope para obtener solo alergias activas (no eliminadas)
     */
    public function scopeActivas($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope para incluir alergias eliminadas
     */
    public function scopeWithInactivas($query)
    {
        return $query->withTrashed();
    }

    /**
     * Marcar como eliminada (baja lógica)
     */
    public function marcarComoEliminada()
    {
        $this->deleted_at = now();
        return $this->save();
    }

    /**
     * Restaurar alergia eliminada
     */
    public function restaurar()
    {
        $this->deleted_at = null;
        return $this->save();
    }

    /**
     * Verificar si está eliminada
     */
    public function isEliminada()
    {
        return !is_null($this->deleted_at);
    }
}