<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InteraccionSwipeUsuario extends Model
{
    use HasFactory;

    protected $table = 'interacciones_swipe_usuarios';
    
    protected $fillable = [
        'usuario_id',
        'mascota_id',
        'oferta_id',
        'tipo_interaccion',
        'fecha_interaccion'
    ];
    
    protected $casts = [
        'fecha_interaccion' => 'datetime'
    ];
    
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
    
    public function mascota()
    {
        return $this->belongsTo(Mascota::class);
    }
    
    public function oferta()
    {
        return $this->belongsTo(OfertaAdopcion::class, 'oferta_id', 'id_oferta');
    }
}