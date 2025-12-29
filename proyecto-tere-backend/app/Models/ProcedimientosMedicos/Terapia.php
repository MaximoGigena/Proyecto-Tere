<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TiposProcedimientos\TipoTerapia;

class Terapia extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_terapia_id',
        'fecha_inicio',
        'frecuencia',
        'duracion_tratamiento',
        'fecha_fin',
        'evolucion',
        'recomendaciones_tutor',
        'observaciones',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'frecuencia' => 'string',
        'evolucion' => 'string',
    ];

    /**
     * Obtener el tipo de terapia asociado.
     */
    public function tipoTerapia()
    {
        return $this->belongsTo(TipoTerapia::class);
    }

    /**
     * Obtener los valores permitidos para el campo 'frecuencia'.
     */
    public static function getFrecuenciasPermitidas()
    {
        return ['diaria', 'semanal', 'quincenal', 'mensual', 'personalizada'];
    }

    /**
     * Obtener los valores permitidos para el campo 'evolucion'.
     */
    public static function getEvolucionesPermitidas()
    {
        return ['mejoria', 'estable', 'empeoramiento'];
    }

    /**
     * Scope para filtrar por frecuencia.
     */
    public function scopePorFrecuencia($query, $frecuencia)
    {
        return $query->where('frecuencia', $frecuencia);
    }

    /**
     * Scope para filtrar por evolución.
     */
    public function scopePorEvolucion($query, $evolucion)
    {
        return $query->where('evolucion', $evolucion);
    }

    /**
     * Scope para terapias activas (sin fecha fin o fecha fin en el futuro).
     */
    public function scopeActivas($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>', now());
        });
    }

    /**
     * Scope para terapias finalizadas.
     */
    public function scopeFinalizadas($query)
    {
        return $query->whereNotNull('fecha_fin')
                    ->where('fecha_fin', '<=', now());
    }

    /**
     * Verificar si la terapia está activa.
     */
    public function estaActiva()
    {
        return is_null($this->fecha_fin) || $this->fecha_fin > now();
    }

    /**
     * Calcular la fecha de finalización estimada.
     */
    public function calcularFechaFinEstimada()
    {
        if (!$this->fecha_inicio || !$this->duracion_tratamiento) {
            return null;
        }

        // Parsear duración del tratamiento (ej: "3 meses", "10 sesiones")
        $duracion = $this->duracion_tratamiento;
        
        if (str_contains($duracion, 'mes')) {
            $meses = (int) $duracion;
            return $this->fecha_inicio->copy()->addMonths($meses);
        } elseif (str_contains($duracion, 'semana')) {
            $semanas = (int) $duracion;
            return $this->fecha_inicio->copy()->addWeeks($semanas);
        }

        return null;
    }
}