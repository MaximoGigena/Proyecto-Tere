<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudAdopcion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_adopcion';
    protected $primaryKey = 'idSolicitud';

    protected $fillable = [
        'idUsuarioSolicitante',
        'idMascota',
        'estadoSolicitud',
        'aceptóTerminos',
        'fechaSolicitud'
    ];

    protected $casts = [
        'fechaSolicitud' => 'datetime',
        'aceptóTerminos' => 'boolean'
    ];

    // Relación con el usuario solicitante - CORREGIDA
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuarioSolicitante', 'id');
    }

    // Agregar una relación directa al modelo Usuario (si es necesario)
    public function usuarioDetalles()
    {
        return $this->hasOneThrough(
            Usuario::class,
            User::class,
            'id', // Foreign key en User
            'id', // Foreign key en Usuario
            'idUsuarioSolicitante', // Local key en SolicitudAdopcion
            'userable_id' // Foreign key en User que apunta a Usuario
        )->where('userable_type', Usuario::class);
    }

    // Relación con la mascota
    public function mascota()
    {
        return $this->belongsTo(Mascota::class, 'idMascota', 'id');
    }

    // Scope para solicitudes activas
    public function scopeActivas($query)
    {
        return $query->whereIn('estadoSolicitud', ['pendiente', 'aprobada']);
    }

    // Scope para solicitudes del usuario
    public function scopeDeUsuario($query, $userId)
    {
        return $query->where('idUsuarioSolicitante', $userId);
    }

    // Verificar si puede ser cancelada
    public function puedeCancelar()
    {
        return in_array($this->estadoSolicitud, ['pendiente']);
    }

    // Marcar como cancelada
    public function cancelar()
    {
        $this->estadoSolicitud = 'cancelada';
        return $this->save();
    }

    // Obtener estado con color
    public function getEstadoConColorAttribute()
    {
        $estados = [
            'pendiente' => ['text' => 'Pendiente', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-100'],
            'aprobada' => ['text' => 'Aprobada', 'color' => 'text-green-600', 'bg' => 'bg-green-100'],
            'rechazada' => ['text' => 'Rechazada', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
            'cancelada' => ['text' => 'Cancelada', 'color' => 'text-gray-600', 'bg' => 'bg-gray-100'],
            'expirada' => ['text' => 'Expirada', 'color' => 'text-orange-600', 'bg' => 'bg-orange-100'],
        ];

        return $estados[$this->estadoSolicitud] ?? ['text' => 'Desconocido', 'color' => 'text-gray-600', 'bg' => 'bg-gray-100'];
    }
}