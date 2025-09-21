<?php
// app/Models/UbicacionUsuario.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionUsuario extends Model
{
    protected $table = 'user_locations';
    
    protected $fillable = [
        'usuario_id',
        'latitude',
        'longitude',
        'location',
        'location_updated_at'
    ];
    
    protected $casts = [
        'location_updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float'
    ];
    
    /**
     * Relación con el usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
    
    /**
     * Scope para ubicaciones recientes
     */
    public function scopeRecientes($query, $horas = 24)
    {
        return $query->where('location_updated_at', '>=', now()->subHours($horas));
    }
}