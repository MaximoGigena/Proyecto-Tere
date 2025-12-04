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
        return $this->hasOne(CaracteristicasUsuario::class, 'usuario_id');
    }

    // AGREGAR: relación con contacto
    public function contacto()
    {
        return $this->hasOne(ContactoUsuario::class, 'usuario_id');
    }

     /**
     * Relación con las ubicaciones del usuario
     */
    public function ubicaciones()
    {
        return $this->hasMany(UbicacionUsuario::class, 'usuario_id'); // Usar 'usuario_id'
    }
    
    /**
     * Obtener la ubicación más reciente
     */
    public function ubicacionActual()
    {
        return $this->hasOne(UbicacionUsuario::class, 'usuario_id')->latestOfMany('location_updated_at');
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
}
