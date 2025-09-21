<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MascotaFoto extends Model
{
    protected $table = 'mascota_fotos';

    protected $fillable = [
        'mascota_id',
        'ruta_foto', 
        'es_principal',
    ];

    public function mascota()
    {
        return $this->belongsTo(Mascota::class);
    }

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->ruta_foto);
    }

}
