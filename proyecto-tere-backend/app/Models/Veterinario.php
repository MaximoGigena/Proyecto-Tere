<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Veterinario extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_completo',
        'matricula',
        'especialidad',
        'foto',
        'activo',    
        'user_type',
        'google_id',
        'estado',
    ];

    protected $table = 'veterinarios';

    // Estados posibles
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($veterinario) {
            if (empty($veterinario->estado)) {
                $veterinario->estado = self::ESTADO_PENDIENTE;
            }
        });
    }

    /**
     * Scope para veterinarios pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para veterinarios aprobados
     */
    public function scopeAprobados($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }

    /**
     * Scope para veterinarios rechazados
     */
    public function scopeRechazados($query)
    {
        return $query->where('estado', self::ESTADO_RECHAZADO);
    }

    /**
     * Scope para veterinarios activos (aprobados y activo=true)
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO)
                    ->where('activo', true);
    }

    /**
     * Verificar si el veterinario está pendiente
     */
    public function estaPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verificar si el veterinario está aprobado
     */
    public function estaAprobado()
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    /**
     * Verificar si el veterinario está rechazado
     */
    public function estaRechazado()
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }

    public function caracteristicas(): HasOne
    {
        return $this->hasOne(CaracteristicasVeterinario::class);
    }

    public function mediosContacto(): HasMany
    {
        return $this->hasMany(ContactoVeterinario::class);
    }
    
    // Relación polimórfica con User
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    // Accesor para email (lo obtiene desde User)
    public function getEmailAttribute()
    {
        return $this->user ? $this->user->email : null;
    }

    // Accesor para email_profesional (alias de email)
    public function getEmailProfesionalAttribute()
    {
        return $this->user ? $this->user->email : null;
    }
}