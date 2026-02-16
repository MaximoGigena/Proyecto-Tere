<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricaUsuario extends Model
{
    use HasFactory;

    protected $table = 'metricas_usuarios';
    
    protected $fillable = [
        'fecha',
        'tipo_reporte',
        'tipo_usuario',
        'datos',
        'total_usuarios',
        'usuarios_activos',
        'usuarios_nuevos',
        'tasa_crecimiento',
        'dau',
        'mau',
        'ratio_dau_mau'
    ];
    
    protected $casts = [
        'fecha' => 'date',
        'datos' => 'array',
        'tasa_crecimiento' => 'float',
        'dau' => 'float',
        'mau' => 'float',
        'ratio_dau_mau' => 'float'
    ];
}