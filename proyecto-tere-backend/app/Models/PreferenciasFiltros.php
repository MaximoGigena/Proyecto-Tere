<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferenciasFiltros extends Model
{
    use HasFactory;

    protected $table = 'preferencias_filtros';

    protected $fillable = [
        'user_id',
        'especies',
        'sexos',
        'rangos_edad',
        'ubicacion_nombre',
        'latitud',
        'longitud',
        'radio_km',
        'nombre_filtro',
        'es_activo'
    ];

    protected $casts = [
        'especies' => 'array',
        'sexos' => 'array',
        'rangos_edad' => 'array',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'radio_km' => 'integer',
        'es_activo' => 'boolean'
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener filtros formateados para la API
     */
    public function getFiltrosFormateados(): array
    {
        $filtros = [];
        
        if (!empty($this->especies)) {
            $filtros['especie'] = $this->especies;
        }
        
        if (!empty($this->sexos)) {
            $filtros['sexo'] = count($this->sexos) === 2 ? 'Macho y Hembra' : $this->sexos[0];
        }
        
        if (!empty($this->rangos_edad)) {
            $filtros['edad'] = $this->rangos_edad;
        }
        
        if ($this->latitud && $this->longitud) {
            $filtros['ubicacion'] = $this->ubicacion_nombre;
            $filtros['coordenadas'] = [
                'lat' => $this->latitud,
                'lon' => $this->longitud
            ];
            $filtros['radio'] = $this->radio_km;
        }
        
        return $filtros;
    }
}