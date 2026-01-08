<?php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoDiagnostico;
use App\Models\ProcesoMedico;

class Diagnostico extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_diagnostico_id',
        'nombre',
        'fecha_diagnostico',
        'estado',
        'diagnosticos_diferenciales',
        'examenes_complementarios',
        'conducta_terapeutica',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_diagnostico' => 'date',
        'estado' => 'string',
    ];

    /**
     * Obtener el tipo de diagnóstico asociado.
     */
    public function tipoDiagnostico()
    {
        return $this->belongsTo(TipoDiagnostico::class);
    }

    /**
     * Obtener los valores permitidos para el campo 'estado'.
     */
    public static function getEstadosPermitidos()
    {
        return ['activo', 'resuelto', 'cronico', 'seguimiento', 'sospecha'];
    }

    /**
     * Obtener los valores para un dropdown de estados.
     */
    public static function getEstadosDropdown()
    {
        return [
            'activo' => 'Activo',
            'resuelto' => 'Resuelto',
            'cronico' => 'Crónico',
            'seguimiento' => 'En seguimiento',
            'sospecha' => 'Sospecha',
        ];
    }

    /**
     * Scope para filtrar por tipo de diagnóstico.
     */
    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_diagnostico_id', $tipoId);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para diagnósticos activos.
     */
    public function scopeActivos($query)
    {
        return $query->whereIn('estado', ['activo', 'cronico', 'seguimiento']);
    }

    /**
     * Scope para diagnósticos resueltos.
     */
    public function scopeResueltos($query)
    {
        return $query->where('estado', 'resuelto');
    }

    /**
     * Scope para diagnósticos en seguimiento.
     */
    public function scopeEnSeguimiento($query)
    {
        return $query->where('estado', 'seguimiento');
    }

    /**
     * Scope para buscar por nombre o diagnóstico.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('observaciones', 'like', "%{$termino}%")
                    ->orWhere('diagnosticos_diferenciales', 'like', "%{$termino}%");
    }

    /**
     * Verificar si el diagnóstico está activo.
     */
    public function estaActivo()
    {
        return in_array($this->estado, ['activo', 'cronico', 'seguimiento']);
    }

    /**
     * Marcar diagnóstico como resuelto.
     */
    public function marcarComoResuelto()
    {
        $this->estado = 'resuelto';
        return $this->save();
    }

    /**
     * Obtener los diagnósticos diferenciales como array.
     */
    public function getDiagnosticosDiferencialesArrayAttribute()
    {
        if (empty($this->diagnosticos_diferenciales)) {
            return [];
        }

        return explode("\n", $this->diagnosticos_diferenciales);
    }

    /**
     * Obtener los exámenes complementarios como array.
     */
    public function getExamenesComplementariosArrayAttribute()
    {
        if (empty($this->examenes_complementarios)) {
            return [];
        }

        return explode("\n", $this->examenes_complementarios);
    }

    /**
     * Formatear la fecha de diagnóstico para mostrar.
     */
    public function getFechaDiagnosticoFormateadaAttribute()
    {
        return $this->fecha_diagnostico->format('d/m/Y');
    }

     public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }
}