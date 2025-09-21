<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactoUsuario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'usuario_contacto';

    protected $fillable = [
        'usuario_id',
        'dni',
        'telefono',
        'email',
        'nombre_completo'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}