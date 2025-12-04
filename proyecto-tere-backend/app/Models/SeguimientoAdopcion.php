<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoAdopcion extends Model
{
    protected $table = 'seguimientos_adopcion';
    protected $primaryKey = 'id_seguimiento';
    
    protected $fillable = [
        'id_proceso',
        'id_usuario',
        'estado_anterior',
        'estado_nuevo',
        'observaciones',
        'tipo_evento',
        'datos_adicionales'
    ];
    
    protected $casts = [
        'datos_adicionales' => 'array',
        'created_at' => 'datetime'
    ];
    
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(ProcesoAdopcion::class, 'id_proceso', 'id_proceso');
    }
    
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}