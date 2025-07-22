<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';

    public function caracteristicas()
    {
        return $this->hasMany(CaracteristicasUsuario::class, 'usuario_id');
    }
}
