<?php
//caracterisitcas_usuarios.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaracteristicasUsuario extends Model
{
    protected $fillable = [
        'tipoVivienda',
        'ocupacion',
        'experiencia',
        'convivenciaNiños',
        'convivenciaMascotas',
        'descripción',
        'usuario_id'
    ];

    protected $table = 'caracteristicas_usuarios';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}

