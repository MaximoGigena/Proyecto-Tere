<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaracteristicasUsuario extends Model
{
    protected $table = 'caracteristicas_usuario';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}

