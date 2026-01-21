<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\User;
use App\Models\CaracteristicasUsuario;
use App\Models\ContactoUsuario;
use App\Models\UbicacionUsuario;
use App\Models\UsuarioFoto;
use App\Models\SolicitudAdopcion;
use App\Traits\Auditable;

class Usuario extends Model
{
    use Auditable;

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
        return $this->hasOne(CaracteristicasUsuario::class, 'usuario_id');
    }

    // AGREGAR: relación con contacto
    public function contacto()
    {
        return $this->hasOne(ContactoUsuario::class, 'usuario_id');
    }

    public function fotos()
    {
        return $this->hasMany(UsuarioFoto::class);
    }

    // En el modelo User.php, añade:
    public function puedeSolicitarAdopcion($mascotaId)
    {
        // Verificar límite de solicitudes pendientes
        $solicitudesPendientes = $this->solicitudesAdopcion()
            ->whereIn('estadoSolicitud', ['pendiente'])
            ->count();
        
        if ($solicitudesPendientes >= 5) { // Límite de 5 solicitudes pendientes
            return false;
        }
        
        // Verificar si ya tiene solicitud para esta mascota
        $solicitudExistente = $this->solicitudesAdopcion()
            ->where('idMascota', $mascotaId)
            ->whereIn('estadoSolicitud', ['pendiente', 'aprobada'])
            ->exists();
        
        return !$solicitudExistente;
    }

    // Relación en User.php
    public function solicitudesAdopcion()
    {
        return $this->hasMany(SolicitudAdopcion::class, 'idUsuarioSolicitante');
    }

    // En App\Models\Usuario
    public function ubicaciones()
    {
        // Relación directa a través de User
        return $this->hasManyThrough(
            UbicacionUsuario::class,
            User::class,
            'userable_id', // Foreign key on users table
            'user_id',     // Foreign key on ubicaciones table
            'id',          // Local key on usuarios table
            'id'           // Local key on users table
        )->where('users.userable_type', Usuario::class);
    }

    public function ubicacionActual()
    {
        return $this->hasOneThrough(
            UbicacionUsuario::class,
            User::class,
            'userable_id', // Foreign key on users table
            'user_id',     // Foreign key on ubicaciones table
            'id',          // Local key on usuarios table
            'id'           // Local key on users table
        )->where('users.userable_type', Usuario::class)
        ->latestOfMany('location_updated_at');
    }
}
