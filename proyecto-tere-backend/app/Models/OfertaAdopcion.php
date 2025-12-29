<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class OfertaAdopcion extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'ofertas_adopcion';
    protected $primaryKey = 'id_oferta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_mascota',
        'id_usuario_responsable',
        'estado_oferta',
        'permiso_historial_medico',
        'permiso_contacto_tutor',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permiso_historial_medico' => 'boolean',
        'permiso_contacto_tutor' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Estados válidos para la oferta.
     */
    public const ESTADOS = [
        'PUBLICADA' => 'publicada',
        'PAUSADA' => 'pausada',
        'EN_PROCESO' => 'en_proceso',
        'CERRADA' => 'cerrada',
        'CANCELADA' => 'cancelada',
    ];

    /**
     * Relación con la mascota.
     */
    public function mascota()
    {
        return $this->belongsTo(Mascota::class, 'id_mascota');
    }

    /**
     * Relación con el usuario responsable.
     */
    public function usuarioResponsable()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_responsable');
    }

    /**
     * Scope para ofertas activas (publicadas o en proceso).
     */
    public function scopeActivas($query)
    {
        return $query->whereIn('estado_oferta', ['publicada', 'en_proceso']);
    }

    /**
     * Scope para ofertas publicadas.
     */
    public function scopePublicadas($query)
    {
        return $query->where('estado_oferta', 'publicada');
    }

    /**
     * Verificar si la oferta está publicada.
     */
    public function estaPublicada(): bool
    {
        return $this->estado_oferta === 'publicada';
    }

    /**
     * Verificar si tiene permiso de historial médico.
     */
    public function tienePermisoHistorialMedico(): bool
    {
        return $this->permiso_historial_medico === true;
    }

    /**
     * Verificar si tiene permiso de contacto con tutor.
     */
    public function tienePermisoContactoTutor(): bool
    {
        return $this->permiso_contacto_tutor === true;
    }
}