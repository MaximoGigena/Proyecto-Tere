<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UsuarioFoto extends Model
{
    protected $table = 'usuario_fotos';

    protected $fillable = [
        'usuario_id',   
        'ruta_foto',
        'es_principal',
    ];

    /**
     * Accessor para obtener la URL completa de la foto
     */
    public function getUrlFotoAttribute()
    {
        if ($this->ruta_foto) {
            return asset('storage/' . $this->ruta_foto);
        }
        return null;
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
