<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FotoVeterinario extends Model
{
    use HasFactory;

    protected $table = 'fotos_veterinarios';

    protected $fillable = [
        'veterinario_id',
        'ruta',
        'orden',
        'tipo',
        'activa'
    ];

    protected $casts = [
        'activa' => 'boolean',
        'orden' => 'integer'
    ];

    /**
     * Relación con Veterinario
     */
    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class);
    }

    /**
     * Accesor para obtener la URL completa de la foto
     */
    public function getUrlAttribute()
    {
        if (empty($this->ruta)) {
            return null;
        }
        
        // Devolver URL absoluta
        return asset('storage/' . $this->ruta);
    }
        
    /**
     * Método para verificar si el archivo existe físicamente
     */
    public function existeFisicamente()
    {
        return Storage::disk('public')->exists($this->ruta);
    }
}