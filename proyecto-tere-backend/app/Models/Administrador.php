<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Administrador extends Model
{
    use HasFactory;

    // Nombre de la tabla (opcional si sigue la convención)
    protected $table = 'administradores';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre_completo',
        'nivel_acceso',
        'ultimo_login',
        'activo',
        'user_type',
        'google_id',
    ];

    // Casting de tipos de datos
    protected $casts = [
        'ultimo_login' => 'datetime',
        'activo' => 'boolean',
    ];

    // Relación polimórfica con User
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }    
}
