<?php
// app/Models/UbicacionUsuario.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Traits\Auditable;

class UbicacionUsuario extends Model
{
    use Auditable;

    protected $table = 'user_locations';
    
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'location',
        'location_updated_at',
        'country',
        'country_code',
        'state',
        'city',
        'source',
        'accuracy',
    ];
    
    protected $casts = [
        'location_updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float'
    ];
    
    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Para compatibilidad
    public function usuario()
    {
        if ($this->user && $this->user->userable_type === 'App\Models\Usuario') {
            return $this->user->userable;
        }
        return null;
    }

    /**
     * Scope para ubicaciones recientes
     */
    public function scopeRecientes($query, $horas = 24)
    {
        return $query->where('location_updated_at', '>=', now()->subHours($horas));
    }

    /**
     * Scope para ubicaciones de un usuario específico
     */
    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}