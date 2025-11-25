<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoRevision;

class Revision extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
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
}