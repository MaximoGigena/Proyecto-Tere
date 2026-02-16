<?php
// app/Models/EjecucionReporteUsuario.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class EjecucionReporteUsuario extends Model
{
    use Auditable;

    protected $table = 'ejecuciones_reportes';

    protected $fillable = [
        'reporte_id',
        'user_id',
        'parametros',
        'resultados',
        'estado',
        'mensaje_error',
        'tiempo_ejecucion',
        'tamano_datos',
        'formato',
        'ruta_archivo'
    ];

    protected $casts = [
        'parametros' => 'array',
        'resultados' => 'array',
        'tiempo_ejecucion' => 'float'
    ];

    // Estados de ejecución
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PROCESANDO = 'procesando';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_FALLIDO = 'fallido';
    const ESTADO_CANCELADO = 'cancelado';

    // Formatos de salida
    const FORMATO_JSON = 'json';
    const FORMATO_PDF = 'pdf';
    const FORMATO_EXCEL = 'excel';
    const FORMATO_CSV = 'csv';


    /**
     * Relación con el usuario que ejecutó
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(ReportesUsuarios::class, 'reporte_id');
    }

    /**
     * Scope para ejecuciones exitosas
     */
    public function scopeExitosas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    /**
     * Scope para ejecuciones fallidas
     */
    public function scopeFallidas($query)
    {
        return $query->where('estado', self::ESTADO_FALLIDO);
    }

    /**
     * Scope para ejecuciones recientes
     */
    public function scopeRecientes($query, $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    /**
     * Obtener estadísticas de ejecución
     */
    public function getEstadisticasAttribute(): array
    {
        return [
            'tiempo_promedio' => self::where('reporte_id', $this->reporte_id)
                ->exitosas()
                ->avg('tiempo_ejecucion') ?? 0,
            'total_ejecuciones' => self::where('reporte_id', $this->reporte_id)->count(),
            'exitosas' => self::where('reporte_id', $this->reporte_id)
                ->exitosas()
                ->count(),
            'fallidas' => self::where('reporte_id', $this->reporte_id)
                ->fallidas()
                ->count()
        ];
    }
}