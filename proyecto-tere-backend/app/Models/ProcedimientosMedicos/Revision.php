<?php
// app/Models/ProcedimientosMedicos/Revision.php

namespace App\Models\ProcedimientosMedicos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoRevision;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProcesoMedico;

class Revision extends Model
{
    use HasFactory;

    protected $table = 'revisiones';

    protected $fillable = [
        'tipo_revision_id',
        'fecha_revision',
        'nivel_urgencia',
        'motivo_consulta',
        'diagnostico',
        'fecha_proxima_revision',
        'indicaciones_medicas',
        'recomendaciones_tutor',
    ];

    protected $casts = [
        'fecha_revision' => 'datetime',
        'fecha_proxima_revision' => 'date',
    ];

    /**
     * Get the tipo_revision that owns the Revision
     */
    public function tipoRevision()
    {
        return $this->belongsTo(TipoRevision::class);
    }

    /**
     * Get the proceso medico asociado
     */
    public function procesoMedico()
    {
        return $this->morphOne(ProcesoMedico::class, 'procesable');
    }
}