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
        'userable_id'
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
        return $this->userable_type === 'App\Models\Administrador';
    }

    public function getNombreAttribute()
    {
        return $this->userable->nombre ?? 
           $this->userable->nombre_completo ?? 
           $this->userable->nombre_completo ?? // para admin y vet
           null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
