<?php

namespace App\Models\TiposProcedimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Veterinario;

class TipoFarmaco extends Model
{
    use HasFactory, softDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipos_farmaco';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre_comercial',
        'nombre_generico',
        'composicion',
        'categoria',
        'categoria_otro',
        'especie',
        'dosis',
        'unidad',
        'frecuencia_unidad',
        'frecuencia',
        'via_administracion',
        'indicaciones_clinicas',
        'contraindicaciones',
        'interacciones_medicamentosas',
        'reacciones_adversas',
        'fabricante',
        'recomendaciones_clinicas',
        'observaciones',
        'veterinario_id',
        'activo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dosis' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dosis' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    public function veterinario()
    {
        return $this->belongsTo(Veterinario::class, 'veterinario_id');
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para filtrar por especie
     */
    public function scopePorEspecie($query, $especie)
    {
        return $query->where('especie', $especie)
                    ->orWhere('especie', 'todos')
                    ->orWhere('especie', 'ninguna');
    }

    /**
     * Scope para filtrar por vía de administración
     */
    public function scopePorVia($query, $via)
    {
        return $query->where('via_administracion', $via);
    }

    /**
     * Scope para búsqueda por nombre (comercial o genérico)
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre_comercial', 'like', "%{$termino}%")
                    ->orWhere('nombre_generico', 'like', "%{$termino}%");
    }

    /**
     * Get dosis completa formateada
     */
    public function getDosisCompletaAttribute(): string
    {
        return "{$this->dosis} {$this->unidad} {$this->frecuencia_unidad}";
    }

    /**
     * Get categoría final (si es "otro" usa categoria_otro)
     */
    public function getCategoriaFinalAttribute(): string
    {
        return $this->categoria === 'otro' && $this->categoria_otro 
            ? $this->categoria_otro 
            : $this->categoria;
    }

    /**
     * Get descripción completa del fármaco
     */
    public function getDescripcionCompletaAttribute(): string
    {
        return "{$this->nombre_comercial} ({$this->nombre_generico}) - {$this->composicion}";
    }

    /**
     * Get vía de administración en texto legible
     */
    public function getViaTextoAttribute(): string
    {
        return match($this->via_administracion) {
            'oral' => 'Oral',
            'subcutanea' => 'Subcutánea',
            'intramuscular' => 'Intramuscular',
            'intravenosa' => 'Intravenosa',
            'topica' => 'Tópica',
            'oftalmica' => 'Oftálmica',
            'otica' => 'Ótica',
            'otra' => 'Otra',
            default => $this->via_administracion,
        };
    }

    /**
     * Get categoría en texto legible
     */
    public function getCategoriaTextoAttribute(): string
    {
        return match($this->categoria) {
            'analgesico' => 'Analgésico',
            'antibiotico' => 'Antibiótico',
            'antiparasitario' => 'Antiparasitario',
            'antiinflamatorio' => 'Antiinflamatorio',
            'antifungico' => 'Antifúngico',
            'antiviral' => 'Antiviral',
            'anestesico' => 'Anestésico',
            'otro' => $this->categoria_otro ?: 'Otro',
            default => $this->categoria,
        };
    }

    /**
     * Get especie en texto legible
     */
    public function getEspecieTextoAttribute(): string
    {
        return match($this->especie) {
            'canino' => 'Canino',
            'felino' => 'Felino',
            'ave' => 'Ave',
            'roedor' => 'Roedor',
            'exotico' => 'Exótico',
            'todos' => 'Todos',
            'ninguna' => 'No aplica',
            default => $this->especie,
        };
    }

    /**
     * Check if dosis es por peso
     */
    public function getEsDosisPorPesoAttribute(): bool
    {
        return $this->frecuencia_unidad === 'kg';
    }

    /**
     * Validación para crear un nuevo tipo de fármaco
     */
    public static function rules(): array
    {
        return [
            'nombre_comercial' => 'required|string|max:255',
            'nombre_generico' => 'required|string|max:255',
            'composicion' => 'required|string',
            'categoria' => 'required|in:analgesico,antibiotico,antiparasitario,antiinflamatorio,antifungico,antiviral,anestesico,otro',
            'categoria_otro' => 'nullable|required_if:categoria,otro|string|max:255',
            'especie' => 'required|in:canino,felino,ave,roedor,exotico,todos,ninguna',
            'dosis' => 'required|numeric|min:0.1',
            'unidad' => 'required|in:mg,ml,UI,mcg,gotas',
            'frecuencia_unidad' => 'required|in:kg,dosis',
            'frecuencia' => 'required|string|max:100',
            'via_administracion' => 'required|in:oral,subcutanea,intramuscular,intravenosa,topica,oftalmica,otica,otra',
            'indicaciones_clinicas' => 'required|string',
            'contraindicaciones' => 'nullable|string',
            'interacciones_medicamentosas' => 'nullable|string',
            'reacciones_adversas' => 'nullable|string',
            'fabricante' => 'nullable|string|max:255',
            'recomendaciones_clinicas' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ];
    }

    /**
     * Mensajes de validación
     */
    public static function messages(): array
    {
        return [
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'nombre_generico.required' => 'El nombre genérico es obligatorio.',
            'composicion.required' => 'La composición es obligatoria.',
            'categoria.required' => 'La categoría terapéutica es obligatoria.',
            'categoria_otro.required_if' => 'Debe especificar la categoría cuando selecciona "Otro".',
            'dosis.required' => 'La dosis terapéutica es obligatoria.',
            'dosis.min' => 'La dosis debe ser al menos 0.1.',
            'frecuencia.required' => 'La frecuencia de administración es obligatoria.',
            'via_administracion.required' => 'La vía de administración es obligatoria.',
            'indicaciones_clinicas.required' => 'Las indicaciones clínicas son obligatorias.',
        ];
    }
}