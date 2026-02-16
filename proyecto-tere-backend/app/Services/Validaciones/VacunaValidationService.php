<?php
// app/Services/Validaciones/VacunaValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoVacuna;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VacunaValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de vacuna específico
     */
    public function validarMascotaParaTipoVacuna(Mascota $mascota, TipoVacuna $tipoVacuna): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoVacuna)) {
            $errors[] = "Esta vacuna no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar edad mínima
        $edadValidation = $this->validarEdadMinima($mascota, $tipoVacuna);
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar estado de castración (si aplica)
        if ($tipoVacuna->requiere_castracion && !$mascota->castrado) {
            $errors[] = "Esta vacuna requiere que la mascota esté castrada.";
        }

        // 4. Validar otras condiciones personalizables
        if ($tipoVacuna->condiciones_especiales) {
            $condicionesErrors = $this->validarCondicionesEspeciales($mascota, $tipoVacuna);
            $errors = array_merge($errors, $condicionesErrors);
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_vacuna_id' => $tipoVacuna->id,
                'especie_valida' => $this->validarEspecie($mascota, $tipoVacuna),
                'edad_valida' => $edadValidation['valido'],
                'edad_actual_meses' => $this->calcularEdadEnMeses($mascota),
                'edad_minima_requerida' => $tipoVacuna->edad_minima,
                'unidad_edad' => $tipoVacuna->edad_unidad
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para la vacuna
     */
    private function validarEspecie(Mascota $mascota, TipoVacuna $tipoVacuna): bool
    {
        // Si el campo especies es un array (por el cast)
        $especiesPermitidas = $tipoVacuna->especies;
        
        // Si es null o vacío, acepta todas las especies
        if (empty($especiesPermitidas)) {
            return true;
        }

        // Si es string, convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Verificar si la especie de la mascota está en las permitidas
        return in_array(strtolower($mascota->especie), 
                       array_map('strtolower', $especiesPermitidas));
    }

    /**
     * Validar edad mínima requerida
     */
    private function validarEdadMinima(Mascota $mascota, TipoVacuna $tipoVacuna): array
    {
        // Si no hay restricción de edad, es válido
        if (empty($tipoVacuna->edad_minima)) {
            return ['valido' => true, 'mensaje' => ''];
        }

        $edadActualMeses = $this->calcularEdadEnMeses($mascota);
        
        if ($edadActualMeses === null) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede determinar la edad de la mascota.'
            ];
        }

        $edadMinimaRequerida = $this->convertirEdadAMeses(
            $tipoVacuna->edad_minima, 
            $tipoVacuna->edad_unidad
        );

        if ($edadActualMeses < $edadMinimaRequerida) {
            $edadActualFormateada = $this->formatearMesesAHumano($edadActualMeses);
            $edadMinimaFormateada = $this->formatearMesesAHumano($edadMinimaRequerida);
            
            return [
                'valido' => false,
                'mensaje' => "La mascota tiene {$edadActualFormateada}, pero esta vacuna requiere mínimo {$edadMinimaFormateada}."
            ];
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Calcular edad en meses desde fecha_nacimiento string
     */
    public function calcularEdadEnMeses(Mascota $mascota): ?float
    {
        try {
            if (!$mascota->fecha_nacimiento) {
                return null;
            }

            // Parsear fecha desde string dd/mm/yyyy
            $partes = explode('/', $mascota->fecha_nacimiento);
            if (count($partes) === 3) {
                $dia = (int)$partes[0];
                $mes = (int)$partes[1];
                $anio = (int)$partes[2];
                
                $fechaNacimiento = Carbon::create($anio, $mes, $dia);
            } else {
                // Intentar otros formatos
                $fechaNacimiento = Carbon::parse($mascota->fecha_nacimiento);
            }

            if ($fechaNacimiento->isFuture()) {
                return 0;
            }

            $diferencia = $fechaNacimiento->diff(Carbon::now());
            
            // Calcular meses con precisión decimal
            $meses = ($diferencia->y * 12) + $diferencia->m;
            $meses += $diferencia->d / 30.44; // Días aproximados a meses
            
            return round($meses, 2);

        } catch (\Exception $e) {
            Log::error('Error calculando edad en meses', [
                'mascota_id' => $mascota->id,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convertir edad a meses según unidad
     */
    private function convertirEdadAMeses(float $edad, string $unidad): float
    {
        switch (strtolower($unidad)) {
            case 'dias':
                return $edad / 30.44;
            case 'semanas':
                return $edad / 4.345;
            case 'meses':
                return $edad;
            case 'años':
                return $edad * 12;
            default:
                return $edad; // Asumir meses por defecto
        }
    }

    /**
     * Formatear meses a texto humano
     */
    private function formatearMesesAHumano(float $meses): string
    {
        if ($meses < 1) {
            $dias = round($meses * 30.44);
            return "{$dias} día" . ($dias !== 1 ? 's' : '');
        } elseif ($meses < 12) {
            return round($meses) . " mes" . (round($meses) !== 1 ? 'es' : '');
        } else {
            $años = floor($meses / 12);
            $mesesRestantes = $meses % 12;
            
            if ($mesesRestantes < 1) {
                return "{$años} año" . ($años !== 1 ? 's' : '');
            } else {
                return "{$años} año" . ($años !== 1 ? 's' : '') . 
                       " y " . round($mesesRestantes) . " mes" . (round($mesesRestantes) !== 1 ? 'es' : '');
            }
        }
    }

    /**
     * Validar condiciones especiales (si existen en el modelo)
     */
    private function validarCondicionesEspeciales(Mascota $mascota, TipoVacuna $tipoVacuna): array
    {
        $errors = [];
        
        // Aquí puedes agregar validaciones específicas según condiciones_especiales
        // Por ejemplo: peso mínimo, estado de salud, etc.
        
        return $errors;
    }

    /**
     * Método para validar antes del registro (puede usarse en Form Request o Controller)
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoVacunaId): array
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tipoVacuna = TipoVacuna::findOrFail($tipoVacunaId);

        return $this->validarMascotaParaTipoVacuna($mascota, $tipoVacuna);
    }
}