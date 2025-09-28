<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudVeterinario extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_veterinarios';

    protected $fillable = [
        'nombre_completo',
        'email',
        'matricula',
        'especialidad',
        'anos_experiencia',
        'descripcion',
        'telefono',
        'email_contacto',
        'fotos',
        'estado',
        'fecha_solicitud',
        'observaciones'
    ];

    protected $casts = [
        'fotos' => 'array',
        'fecha_solicitud' => 'datetime',
        'anos_experiencia' => 'integer'
    ];

    // Estados posibles
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($solicitud) {
            if (empty($solicitud->estado)) {
                $solicitud->estado = self::ESTADO_PENDIENTE;
            }
            if (empty($solicitud->fecha_solicitud)) {
                $solicitud->fecha_solicitud = now();
            }
        });
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para solicitudes aprobadas
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }

    /**
     * Scope para solicitudes rechazadas
     */
    public function scopeRechazadas($query)
    {
        return $query->where('estado', self::ESTADO_RECHAZADO);
    }

    /**
     * Verificar si la solicitud está pendiente
     */
    public function estaPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verificar si la solicitud está aprobada
     */
    public function estaAprobada()
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    /**
     * Verificar si la solicitud está rechazada
     */
    public function estaRechazada()
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }
}