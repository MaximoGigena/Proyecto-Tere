<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'userable_type',
        'userable_id',
        'estado',
        'google_id', 
        'facebook_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

     protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userable()
    {
        return $this->morphTo();
    }

    // ✅ NUEVO: Método para determinar si es login social
    public function isSocialLogin()
    {
        return !is_null($this->google_id) || !is_null($this->facebook_id);
    }

    // ✅ NUEVO: Método para obtener el provider
    public function getSocialProvider()
    {
        if ($this->google_id) return 'google';
        if ($this->facebook_id) return 'facebook';
        return null;
    }

    // Métodos helper
     public function isUsuario()
    {
        return $this->userable_type === 'App\Models\Usuario';
    }

    public function isVeterinario()
    {
        return $this->userable_type === 'App\Models\Veterinario';
    }

    public function isAdministrador()
    {
        return $this->userable_type === 'App\Models\Administrador'; // ✅ Corregido
    }

     public function estaPendiente()
    {
        if ($this->isVeterinario() && $this->userable) {
            // ✅ Usar strings directamente en lugar de constantes para evitar dependencias
            return $this->userable->estado === 'pendiente';
        }
        return $this->estado === 'pendiente';
    }

    public function estaAprobado()
    {
        if ($this->isVeterinario() && $this->userable) {
            return $this->userable->estado === 'aprobado';
        }
        return $this->estado === 'activo';
    }

    public function estaRechazado()
    {
        if ($this->isVeterinario() && $this->userable) {
            return $this->userable->estado === 'rechazado';
        }
        return $this->estado === 'rechazado' || $this->estado === 'inactivo';
    }

    // ✅ Accesor para obtener el nombre según el tipo de usuario
    public function getNombreAttribute()
    {
        // Si ya tenemos name (de login social), usarlo
        if ($this->name) {
            return $this->name;
        }
        
        if (!$this->userable) return null;

        if ($this->isVeterinario() || $this->isAdministrador()) {
            return $this->userable->nombre_completo;
        } elseif ($this->isUsuario()) {
            return $this->userable->nombre;
        }

        return null;
    }

   // En App\Models\User.php
    /**
     * Obtener las notificaciones del usuario
     */
    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id') // ← Especificar la columna
                    ->activas()
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Obtener notificaciones no leídas
     */
    public function notificacionesNoLeidas()
    {
        return $this->notificaciones()->noLeidas();
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNotificacionesNoLeidas()
    {
        return $this->notificaciones()->where('leida', false)->count();
    }

    /**
     * Verificar si el usuario tiene sanciones activas
     */
    public function tieneSancionesActivas()
    {
        try {
            // Verificar estado del usuario primero (más rápido)
            if (in_array($this->estado, ['suspendido', 'bloqueado'])) {
                Log::info('📋 Usuario tiene estado suspendido/bloqueado', [
                    'user_id' => $this->id,
                    'estado' => $this->estado
                ]);
                return true;
            }
            
            // Verificar sanciones en la tabla Sancion
            $sancionesActivas = Sancion::activas()
                ->where('usuario_id', $this->id)
                ->exists();
                
            if ($sancionesActivas) {
                Log::info('📋 Usuario tiene sanciones activas en tabla', [
                    'user_id' => $this->id,
                    'sanciones_activas' => $sancionesActivas
                ]);
                
                // Si hay sanciones activas, actualizar estado del usuario
                if ($this->estado !== 'suspendido') {
                    $this->update(['estado' => 'suspendido']);
                    Log::info('🔄 Estado actualizado a suspendido', [
                        'user_id' => $this->id
                    ]);
                }
                
                return true;
            }
            
            Log::info('📋 Usuario NO tiene sanciones activas', [
                'user_id' => $this->id,
                'estado' => $this->estado
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('❌ Error en tieneSancionesActivas:', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener las sanciones activas del usuario
     */
    public function sancionesActivas()
    {
        return $this->hasMany(Sancion::class, 'usuario_id')
                    ->activas()
                    ->orderBy('fecha_inicio', 'desc');
    }

    /**
     * Obtener la información de sanción activa más relevante
     */
    public function obtenerInformacionSancion()
    {
        $sanciones = $this->sancionesActivas()->get();
        
        if ($sanciones->isEmpty()) {
            return null;
        }
        
        // Tomar la sanción más reciente
        $sancion = $sanciones->first();
        
        return [
            'id' => $sancion->id,
            'tipo' => $sancion->tipo,
            'razon' => $sancion->razon,
            'descripcion' => $sancion->descripcion,
            'fecha_inicio' => $sancion->fecha_inicio,
            'fecha_fin' => $sancion->fecha_fin,
            'duracion_dias' => $sancion->duracion_dias,
            'es_permanente' => $sancion->tipo === 'BLOQUEO_PERMANENTE' || is_null($sancion->fecha_fin),
            'puede_apelar' => $sancion->tipo !== 'BLOQUEO_PERMANENTE' && $sancion->estado === 'ACTIVA',
            'restricciones' => $sancion->restricciones
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    
}
