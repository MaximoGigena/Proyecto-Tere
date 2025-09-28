<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'userable_type',
        'userable_id',
        'estado',
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
        if (!$this->userable) return null;

        if ($this->isVeterinario() || $this->isAdministrador()) {
            return $this->userable->nombre_completo;
        } elseif ($this->isUsuario()) {
            return $this->userable->nombre;
        }

        return null;
    }


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    
}
