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
        'google_id' 
    ];

    protected $table = 'veterinarios';

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