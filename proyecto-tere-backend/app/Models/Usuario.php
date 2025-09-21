<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Usuario extends Model
{
    protected $fillable = [
        'nombre',
        'edad',
        'foto_perfil',
        'activo',    
        'user_type',
        'google_id'        
    ];
    
    protected $table = 'usuarios';

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function caracteristicas()
    {
        return $this->hasMany(CaracteristicasUsuario::class, 'usuario_id');
    }

     /**
     * Relación con las ubicaciones del usuario
     */
    public function ubicaciones()
    {
        return $this->hasMany(UbicacionUsuario::class, 'user_id');
    }
    
    /**
     * Obtener la ubicación más reciente
     */
    public function ubicacionActual()
    {
        return $this->hasOne(UbicacionUsuario::class, 'user_id')->latestOfMany('location_updated_at');
    }

    public function fotos()
    {
        return $this->hasMany(UsuarioFoto::class);
    }
}
