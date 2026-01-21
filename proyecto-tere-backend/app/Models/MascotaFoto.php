<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected $appends = ['url', 'is_external'];

    // Determinar si es una URL externa
    public function getIsExternalAttribute()
    {
        return Str::startsWith($this->ruta_foto, 'http://') || 
               Str::startsWith($this->ruta_foto, 'https://');
    }

    // En App\Models\MascotaFoto
    public function getUrlAttribute()
    {
        // Si es una URL externa, devolverla directamente
        if (Str::startsWith($this->ruta_foto, 'http://') || 
            Str::startsWith($this->ruta_foto, 'https://')) {
            return $this->ruta_foto;
        }
        
        // Si ya tiene 'storage/' al inicio, usar asset()
        if (Str::startsWith($this->ruta_foto, 'storage/')) {
            return asset($this->ruta_foto);
        }
        
        // Para rutas locales, construir la URL correctamente
        return asset('storage/' . ltrim($this->ruta_foto, '/'));
    }
}