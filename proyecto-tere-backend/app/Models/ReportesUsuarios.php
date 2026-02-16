<?php
// app/Models/ReportesUsuarios.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class ReportesUsuarios extends Model
{
    use Auditable;

    protected $table = 'reportes_usuarios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_reporte',
        'configuracion',
        'user_id',
        'user_type',
        'programado',
        'frecuencia',
        'ultima_generacion',
        'estado',
        'filtros',
        'parametros'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'filtros' => 'array',
        'parametros' => 'array',
        'ultima_generacion' => 'datetime',
        'programado' => 'boolean'
    ];

    // Tipos de reporte
    const TIPO_USUARIOS = 'usuarios';
    const TIPO_VETERINARIOS = 'veterinarios';
    const TIPO_ADOPCIONES = 'adopciones';
    const TIPO_METRICAS = 'metricas';
    const TIPO_PERSONALIZADO = 'personalizado';

    // Estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';
    const ESTADO_PAUSADO = 'pausado';

    // Frecuencias
    const FRECUENCIA_DIARIA = 'diaria';
    const FRECUENCIA_SEMANAL = 'semanal';
    const FRECUENCIA_MENSUAL = 'mensual';
    const FRECUENCIA_TRIMESTRAL = 'trimestral';
    const FRECUENCIA_ANUAL = 'anual';

    /**
     * Relación con el usuario que creó el reporte
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtener las ejecuciones del reporte
     */
    public function ejecuciones(): HasMany
    {
        return $this->hasMany(EjecucionReporteUsuario::class, 'reporte_id');
    }

    /**
     * Scope para reportes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope para reportes programados
     */
    public function scopeProgramados($query)
    {
        return $query->where('programado', true)
                    ->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Verificar si el reporte está programado
     */
    public function estaProgramado(): bool
    {
        return $this->programado && $this->estado === self::ESTADO_ACTIVO;
    }

    /**
     * Obtener la próxima ejecución programada
     */
    public function getProximaEjecucionAttribute()
    {
        if (!$this->estaProgramado()) {
            return null;
        }

        $ultima = $this->ultima_generacion ?: now();
        
        switch ($this->frecuencia) {
            case self::FRECUENCIA_DIARIA:
                return $ultima->addDay();
            case self::FRECUENCIA_SEMANAL:
                return $ultima->addWeek();
            case self::FRECUENCIA_MENSUAL:
                return $ultima->addMonth();
            case self::FRECUENCIA_TRIMESTRAL:
                return $ultima->addMonths(3);
            case self::FRECUENCIA_ANUAL:
                return $ultima->addYear();
            default:
                return null;
        }
    }
}