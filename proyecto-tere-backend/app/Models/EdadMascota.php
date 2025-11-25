<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EdadMascota extends Model
{
    protected $table = 'edades_mascotas';

    protected $fillable = [
        'mascota_id',
        'dias',
        'meses',
        'años',
        'edad_formateada',
        'ultima_actualizacion'
    ];

    protected $casts = [
        'ultima_actualizacion' => 'datetime',
    ];

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }

    /**
     * Actualizar la edad basada en la fecha de nacimiento (acepta ambos formatos)
     */
    public function actualizarDesdeFechaNacimiento($fechaNacimiento): void
    {
        if (!$fechaNacimiento) {
            $this->update([
                'dias' => null,
                'meses' => null,
                'años' => null,
                'edad_formateada' => null,
                'ultima_actualizacion' => now()
            ]);
            return;
        }

        try {
            // Intentar parsear la fecha en diferentes formatos
            $nacimiento = $this->parsearFecha($fechaNacimiento);
            
            if (!$nacimiento || $nacimiento->isFuture()) {
                $this->update([
                    'dias' => null,
                    'meses' => null,
                    'años' => null,
                    'edad_formateada' => 'Fecha inválida',
                    'ultima_actualizacion' => now()
                ]);
                return;
            }

            $hoy = Carbon::now();
            
            // ✅ CORREGIDO: Usar floor() en lugar de round() y calcular correctamente
            $dias = (int) $nacimiento->diffInDays($hoy);
            $años = (int) $nacimiento->diffInYears($hoy);
            
            // Calcular meses totales desde el nacimiento
            $mesesTotales = (int) $nacimiento->diffInMonths($hoy);
            
            // Calcular meses restantes después de restar los años completos
            $mesesRestantes = $mesesTotales - ($años * 12);

            $edadFormateada = $this->formatearEdad($dias, $mesesTotales, $años, $mesesRestantes);

            $this->update([
                'dias' => $dias,
                'meses' => $mesesTotales,
                'años' => $años,
                'edad_formateada' => $edadFormateada,
                'ultima_actualizacion' => now()
            ]);

        } catch (\Exception $e) {
            $this->update([
                'dias' => null,
                'meses' => null,
                'años' => null,
                'edad_formateada' => 'Fecha inválida: ' . $e->getMessage(),
                'ultima_actualizacion' => now()
            ]);
        }
    }

    /**
     * Parsear fecha en diferentes formatos
     */
    private function parsearFecha($fecha)
    {
        // Si es nulo o vacío
        if (empty($fecha)) {
            return null;
        }

        // Si ya es una instancia de Carbon
        if ($fecha instanceof Carbon) {
            return $fecha;
        }

        // Intentar formato DD/MM/YYYY (nuevo formato)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha, $matches)) {
            $dia = (int) $matches[1];
            $mes = (int) $matches[2];
            $anio = (int) $matches[3];
            
            // Validar fecha
            if (checkdate($mes, $dia, $anio)) {
                return Carbon::createFromDate($anio, $mes, $dia);
            }
        }

        // Intentar formato YYYY-MM-DD (formato antiguo de la base de datos)
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fecha, $matches)) {
            $anio = (int) $matches[1];
            $mes = (int) $matches[2];
            $dia = (int) $matches[3];
            
            // Validar fecha
            if (checkdate($mes, $dia, $anio)) {
                return Carbon::createFromDate($anio, $mes, $dia);
            }
        }

        // Intentar parseo automático de Carbon (como fallback)
        try {
            $fechaCarbon = Carbon::parse($fecha);
            if ($fechaCarbon->isValid()) {
                return $fechaCarbon;
            }
        } catch (\Exception $e) {
            // Continuar con el siguiente método
        }

        return null;
    }

    /**
     * Formatear la edad para mostrar - CORREGIDO
     */
    private function formatearEdad(int $dias, int $mesesTotales, int $años, int $mesesRestantes): string
    {
        if ($dias < 30) {
            return "{$dias} " . ($dias === 1 ? 'día' : 'días');
        } else if ($dias < 365) {
            return "{$mesesTotales} " . ($mesesTotales === 1 ? 'mes' : 'meses');
        } else {
            if ($mesesRestantes > 0) {
                return "{$años} " . ($años === 1 ? 'año' : 'años') . " y {$mesesRestantes} " . ($mesesRestantes === 1 ? 'mes' : 'meses');
            }
            return "{$años} " . ($años === 1 ? 'año' : 'años');
        }
    }

    /**
     * Accessor para obtener la edad en formato legible
     */
    public function getEdadCompletaAttribute(): string
    {
        return $this->edad_formateada ?? 'Edad no disponible';
    }

    /**
     * Verificar si necesita actualización (cada 30 días para ahorrar recursos)
     */
    public function necesitaActualizacion(): bool
    {
        return !$this->ultima_actualizacion || 
               $this->ultima_actualizacion->diffInDays(now()) >= 30;
    }
}