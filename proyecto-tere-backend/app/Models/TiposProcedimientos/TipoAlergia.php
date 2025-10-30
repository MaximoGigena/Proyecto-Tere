<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoAlergia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_alergia';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'categoria_otro',
        'reaccion_comun',
        'nivel_riesgo',
        'areas_afectadas',
        'otra_area',
        'tratamiento_recomendado',
        'recomendaciones_clinicas',
        'especie_afectada',
        'desencadenante',
        'conducta_recomendada',
        'observaciones_adicionales',
        'veterinario_id',
        'activo'
    ];

    protected $casts = [
        'areas_afectadas' => 'array'
    ];

    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Accesores para facilitar el uso en las vistas
     */
    public function getCategoriaFinalAttribute(): string
    {
        return $this->categoria === 'otra' 
            ? $this->categoria_otro 
            : $this->categoria;
    }

    public function getAreasAfectadasCompletoAttribute(): string
    {
        $areas = $this->areas_afectadas;
        
        if ($this->otra_area) {
            $areas[] = $this->otra_area;
        }
        
        return implode(', ', $areas);
    }

    /**
     * Accesores para contar caracteres (útil para las validaciones del frontend)
     */
    public function getConductaCaracteresAttribute(): array
    {
        return [
            'actual' => strlen($this->conducta_recomendada ?? ''),
            'maximo' => 500
        ];
    }

    public function getObservacionesCaracteresAttribute(): array
    {
        return [
            'actual' => strlen($this->observaciones_adicionales ?? ''),
            'maximo' => 500
        ];
    }

    /**
     * Scopes para búsquedas
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorRiesgo($query, $riesgo)
    {
        return $query->where('nivel_riesgo', $riesgo);
    }

    public function scopePorEspecie($query, $especie)
    {
        if ($especie === 'todos') {
            return $query;
        }
        
        return $query->where('especie_afectada', $especie)
                    ->orWhere('especie_afectada', 'todos');
    }

    public function scopePorAreaAfectada($query, $area)
    {
        return $query->whereJsonContains('areas_afectadas', $area);
    }

    public function scopePorDesencadenante($query, $desencadenante)
    {
        return $query->where('desencadenante', 'like', "%{$desencadenante}%");
    }

    /**
     * Obtener áreas afectadas predefinidas (como en el frontend)
     */
    public static function getAreasPredefinidas(): array
    {
        return [
            ['value' => 'piel', 'label' => 'Piel'],
            ['value' => 'ojos', 'label' => 'Ojos'],
            ['value' => 'oidos', 'label' => 'Oídos'],
            ['value' => 'respiratorio', 'label' => 'Sistema respiratorio'],
            ['value' => 'digestivo', 'label' => 'Sistema digestivo'],
            ['value' => 'neurologico', 'label' => 'Sistema neurológico'],
            ['value' => 'otro', 'label' => 'Otro']
        ];
    }

    /**
     * Obtener niveles de riesgo con etiquetas
     */
    public static function getNivelesRiesgo(): array
    {
        return [
            'leve' => 'Leve',
            'moderado' => 'Moderado',
            'grave' => 'Grave',
            'muy_grave' => 'Muy grave'
        ];
    }

    /**
     * Obtener categorías con etiquetas
     */
    public static function getCategorias(): array
    {
        return [
            'medicamento' => 'Medicamento',
            'alimento' => 'Alimento',
            'ambiental' => 'Ambiental',
            'contacto' => 'Por contacto',
            'otra' => 'Otra'
        ];
    }

    /**
     * Validar si es de alto riesgo
     */
    public function getEsAltoRiesgoAttribute(): bool
    {
        return in_array($this->nivel_riesgo, ['grave', 'muy_grave']);
    }

    /**
     * Buscar alergias por término (para el buscador)
     */
    public static function buscar($termino)
    {
        return static::where('nombre', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%")
                    ->orWhere('reaccion_comun', 'like', "%{$termino}%")
                    ->orWhere('desencadenante', 'like', "%{$termino}%");
    }
}