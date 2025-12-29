<?php
//caracterisitcas_usuarios.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;
use App\Traits\Auditable;

class CaracteristicasUsuario extends Model
{
    use Auditable;

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

