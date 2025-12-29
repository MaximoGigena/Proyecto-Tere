<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialSancion extends Model
{
    use HasFactory;

    protected $table = 'historial_sanciones';

    protected $fillable = [
        'usuario_id',
        'sancion_id',
        'accion',
        'detalles',
        'realizado_por',
        'ip_address'
    ];

    protected $casts = [
        'detalles' => 'array'
    ];

    // Acciones registradas
    const ACCIONES = [
        'CREACION',
        'ACTUALIZACION',
        'REVOCACION',
        'CUMPLIMIENTO',
        'EXPIRACION'
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function sancion()
    {
        return $this->belongsTo(Sancion::class);
    }

    public function administrador()
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }
}