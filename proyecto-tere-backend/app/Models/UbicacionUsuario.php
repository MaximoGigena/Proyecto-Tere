<?php
// app/Models/UbicacionUsuario.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;
use App\Traits\Auditable;

class UbicacionUsuario extends Model
{
    use Auditable;

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