<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
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
        'fecha_nacimiento',  
        'edad_dias',
        'edad_meses',
        'edad_años', 
        'edad_formateada',
        'ultima_actualizacion_edad',
        'foto_perfil',
        'activo',    
        'user_type',
        'google_id'        
    ];
    
    protected $table = 'usuarios';

    protected $updatingAge = false;

    protected $casts = [
        'fecha_nacimiento' => 'datetime',
        'ultima_actualizacion_edad' => 'datetime',
        'activo' => 'boolean',
    ];

    // Boot del modelo
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($usuario) {
            // Solo actualizar si es un nuevo registro O si cambió la fecha
            // Y evitar recursión
            if (($usuario->wasRecentlyCreated || $usuario->isDirty('fecha_nacimiento')) 
                && !$usuario->updatingAge) { // Bandera para evitar recursión
                
                $usuario->updatingAge = true;
                $usuario->actualizarEdad();
                $usuario->updatingAge = false;
            }
        });
        
        // Eliminar el evento 'created' porque 'saved' ya cubre los nuevos registros
    }

    /**
     * Actualizar la edad basada en la fecha de nacimiento
     */
    public function actualizarEdad(): void
    {
        if (!$this->fecha_nacimiento) {
            $this->updateQuietly([  // 👈 CAMBIA update() por updateQuietly()
                'edad_dias' => null,
                'edad_meses' => null,
                'edad_años' => null,
                'edad_formateada' => null,
                'ultima_actualizacion_edad' => now()
            ]);
            return;
        }

        try {
            $nacimiento = $this->fecha_nacimiento instanceof Carbon 
                ? $this->fecha_nacimiento 
                : Carbon::parse($this->fecha_nacimiento);
            
            if ($nacimiento->isFuture()) {
                $this->updateQuietly([  // 👈 CAMBIA update() por updateQuietly()
                    'edad_dias' => null,
                    'edad_meses' => null,
                    'edad_años' => null,
                    'edad_formateada' => 'Fecha futura',
                    'ultima_actualizacion_edad' => now()
                ]);
                return;
            }

            $hoy = Carbon::now();
            
            // Calcular edades
            $dias = (int) $nacimiento->diffInDays($hoy);
            $años = (int) $nacimiento->diffInYears($hoy);
            $mesesTotales = (int) $nacimiento->diffInMonths($hoy);
            $mesesRestantes = $mesesTotales - ($años * 12);

            $edadFormateada = $this->formatearEdad($años, $mesesRestantes, $dias);

            // 👈 USA updateQuietly() para NO disparar eventos
            $this->updateQuietly([
                'edad_dias' => $dias,
                'edad_meses' => $mesesTotales,
                'edad_años' => $años,
                'edad_formateada' => $edadFormateada,
                'ultima_actualizacion_edad' => now()
            ]);

        } catch (\Exception $e) {
            // 👈 USA updateQuietly()
            $this->updateQuietly([
                'edad_dias' => null,
                'edad_meses' => null,
                'edad_años' => null,
                'edad_formateada' => 'Error calculando edad',
                'ultima_actualizacion_edad' => now()
            ]);
        }
    }

    /**
     * Formatear la edad para mostrar (para personas)
     */
    private function formatearEdad(int $años, int $mesesRestantes, int $dias): string
    {
        if ($años === 0) {
            if ($mesesRestantes === 0) {
                return $dias === 1 ? '1 día' : "{$dias} días";
            }
            return $mesesRestantes === 1 
                ? '1 mes' 
                : "{$mesesRestantes} meses";
        }
        
        if ($años > 0 && $mesesRestantes > 0) {
            $añoTexto = $años === 1 ? 'año' : 'años';
            $mesTexto = $mesesRestantes === 1 ? 'mes' : 'meses';
            return "{$años} {$añoTexto} y {$mesesRestantes} {$mesTexto}";
        }
        
        $añoTexto = $años === 1 ? 'año' : 'años';
        return "{$años} {$añoTexto}";
    }

    /**
     * Verificar si necesita actualización (cada 30 días)
     */
    public function necesitaActualizacionEdad(): bool
    {
        return !$this->ultima_actualizacion_edad || 
               $this->ultima_actualizacion_edad->diffInDays(now()) >= 30;
    }

    /**
     * Accessor para obtener la edad formateada (mantiene compatibilidad)
     */
    public function getEdadAttribute()
    {
        // Si hay fecha de nacimiento y necesita actualización
        if ($this->fecha_nacimiento && $this->necesitaActualizacionEdad()) {
            $this->actualizarEdad();
            $this->refresh();
        }
        
        return $this->edad_formateada ?? 'Edad no disponible';
    }
    
    /**
     * Accessor para obtener edad en años (útil para búsquedas)
     */
    public function getEdadEnAñosAttribute(): ?int
    {
        if ($this->edad_años !== null) {
            return $this->edad_años;
        }
        
        if ($this->fecha_nacimiento) {
            return Carbon::parse($this->fecha_nacimiento)->age;
        }
        
        return null;
    }

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
