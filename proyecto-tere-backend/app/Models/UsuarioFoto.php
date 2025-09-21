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

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
