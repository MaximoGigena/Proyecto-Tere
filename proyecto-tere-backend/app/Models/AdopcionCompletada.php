<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdopcionCompletada extends Model
{
    protected $table = 'adopciones_completadas';
    
    protected $fillable = [
        'mascota_id',
        'tutor_anterior_id',
        'tutor_nuevo_id',
        'proceso_adopcion_id',
        'fecha_adopcion',
        'estado',
        'observaciones'
    ];
    
    protected $casts = [
        'fecha_adopcion' => 'datetime'
    ];
    
    public function mascota()
    {
        return $this->belongsTo(Mascota::class);
    }
    
    public function tutorAnterior()
    {
        return $this->belongsTo(Usuario::class, 'tutor_anterior_id');
    }
    
    public function tutorNuevo()
    {
        return $this->belongsTo(Usuario::class, 'tutor_nuevo_id');
    }
    
    public function proceso()
    {
        return $this->belongsTo(ProcesoAdopcion::class, 'proceso_adopcion_id');
    }
}