<?php
// app/Services/Validaciones/TerapiaValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoTerapia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TerapiaValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de terapia específico
     */
    public function validarMascotaParaTipoTerapia(Mascota $mascota, TipoTerapia $tipoTerapia): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoTerapia)) {
            $errors[] = "Esta terapia no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar requisitos específicos del tipo de terapia
        $requisitosErrors = $this->validarRequisitosEspecificos($mascota, $tipoTerapia);
        if (!empty($requisitosErrors)) {
            $errors = array_merge($errors, $requisitosErrors);
        }

        // 3. Validar contraindicaciones
        $contraindicacionesErrors = $this->validarContraindicaciones($mascota, $tipoTerapia);
        if (!empty($contraindicacionesErrors)) {
            $errors = array_merge($errors, $contraindicacionesErrors);
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_terapia_id' => $tipoTerapia->id,
                'especie_mascota' => $mascota->especie,
                'especies_permitidas' => $tipoTerapia->especies,
                'requisitos_terapia' => $tipoTerapia->requisitos,
                'contraindicaciones' => $tipoTerapia->contraindicaciones
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para la terapia
     */
    private function validarEspecie(Mascota $mascota, TipoTerapia $tipoTerapia): bool
    {
        // Si el campo especies es null o vacío, acepta todas las especies
        if (empty($tipoTerapia->especies)) {
            return true;
        }

        $especiesPermitidas = $tipoTerapia->especies;
        
        // Si es string, podría ser JSON, convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Si después de procesar sigue siendo null, acepta todas
        if (empty($especiesPermitidas)) {
            return true;
        }

        // Verificar si la especie de la mascota está en las permitidas
        return in_array(strtolower($mascota->especie), 
                       array_map('strtolower', $especiesPermitidas));
    }

    /**
     * Validar requisitos específicos del tipo de terapia
     * Esto analiza el campo 'requisitos' del TipoTerapia
     */
    private function validarRequisitosEspecificos(Mascota $mascota, TipoTerapia $tipoTerapia): array
    {
        $errors = [];
        
        // Si no hay requisitos específicos, no hay errores
        if (empty($tipoTerapia->requisitos)) {
            return $errors;
        }

        // Analizar requisitos comunes en el texto
        $requisitos = strtolower($tipoTerapia->requisitos);
        
        // 1. Validar edad mínima (ej: "mayores a 6 meses", "mayor de 1 año")
        $edadErrors = $this->validarRequisitosEdad($mascota, $requisitos);
        $errors = array_merge($errors, $edadErrors);
        
        // 2. Validar castración
        if (strpos($requisitos, 'castrado') !== false || 
            strpos($requisitos, 'esterilizado') !== false) {
            if (!$mascota->castrado) {
                $errors[] = "Esta terapia requiere que la mascota esté castrada/esterilizada.";
            }
        }
        
        // 3. Validar especie específica (ya lo hace validarEspecie, pero podemos ser más específicos)
        if (strpos($requisitos, 'solo') !== false || strpos($requisitos, 'exclusivo') !== false) {
            // Ej: "solo para caninos", "exclusivo para felinos"
            if (preg_match('/solo para (\w+)/', $requisitos, $matches) ||
                preg_match('/exclusivo para (\w+)/', $requisitos, $matches)) {
                $especieRequerida = strtolower($matches[1]);
                if (strpos($especieRequerida, 'canino') !== false && $mascota->especie !== 'canino') {
                    $errors[] = "Esta terapia es exclusiva para caninos.";
                }
                if (strpos($especieRequerida, 'felino') !== false && $mascota->especie !== 'felino') {
                    $errors[] = "Esta terapia es exclusiva para felinos.";
                }
                if (strpos($especieRequerida, 'equino') !== false && $mascota->especie !== 'equino') {
                    $errors[] = "Esta terapia es exclusiva para equinos.";
                }
            }
        }
        
        // 4. Validar condiciones especiales del campo 'requisitos'
        if (strpos($requisitos, 'embarazo') !== false || strpos($requisitos, 'gestación') !== false) {
            // Aquí podrías agregar lógica si tienes campo de embarazo en mascota
            // $errors[] = "Esta terapia no se aplica a hembras embarazadas.";
        }
        
        return $errors;
    }

    /**
     * Validar requisitos de edad desde texto
     */
    private function validarRequisitosEdad(Mascota $mascota, string $requisitos): array
    {
        $errors = [];
        $edadMeses = $this->calcularEdadEnMeses($mascota);
        
        if ($edadMeses === null) {
            $errors[] = "No se puede determinar la edad de la mascota para validar requisitos.";
            return $errors;
        }
        
        // Patrones comunes en requisitos de edad
        // "mayores a 6 meses", "mayor de 1 año", "mínimo 8 semanas"
        
        // Buscar patrones numéricos con unidades
        if (preg_match_all('/(?:mayor(?:es)? (?:a|de)|mínimo|mínima|edad mínima)\s*(\d+)\s*(mes(?:es)?|año|años|semana|semanas|día|días)/i', $requisitos, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $edadRequerida = (float) $match[1];
                $unidad = strtolower($match[2]);
                
                // Convertir a meses
                $edadRequeridaMeses = $this->convertirUnidadAMeses($edadRequerida, $unidad);
                
                if ($edadMeses < $edadRequeridaMeses) {
                    $edadActualFormateada = $this->formatearMesesAHumano($edadMeses);
                    $edadRequeridaFormateada = $this->formatearMesesAHumano($edadRequeridaMeses);
                    
                    $errors[] = "La mascota tiene {$edadActualFormateada}, pero esta terapia requiere mínimo {$edadRequeridaFormateada}.";
                }
            }
        }
        
        // Validar "adultos" o "cachorros"
        if (strpos($requisitos, 'adulto') !== false && $edadMeses < 12) {
            $errors[] = "Esta terapia es solo para mascotas adultas (mínimo 1 año).";
        }
        
        if (strpos($requisitos, 'cachorro') !== false && $edadMeses >= 12) {
            $errors[] = "Esta terapia es solo para cachorros (menores de 1 año).";
        }
        
        return $errors;
    }

    /**
     * Validar contraindicaciones
     */
    private function validarContraindicaciones(Mascota $mascota, TipoTerapia $tipoTerapia): array
    {
        $errors = [];
        
        if (empty($tipoTerapia->contraindicaciones)) {
            return $errors;
        }
        
        $contraindicaciones = strtolower($tipoTerapia->contraindicaciones);
        
        // Ejemplo: Validar si la mascota tiene alguna condición que contraindique la terapia
        // Esto depende de tu modelo de datos de salud de la mascota
        
        // Si tienes un modelo de condiciones médicas de la mascota:
        // if ($mascota->condicionesMedicas()->whereIn('condicion', ['insuficiencia_renal', 'problemas_cardiacos'])->exists()) {
        //     if (strpos($contraindicaciones, 'renal') !== false || 
        //         strpos($contraindicaciones, 'cardíaco') !== false) {
        //         $errors[] = "Esta terapia está contraindicada para mascotas con problemas renales o cardíacos.";
        //     }
        // }
        
        return $errors;
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
     * Convertir unidad de tiempo a meses
     */
    private function convertirUnidadAMeses(float $valor, string $unidad): float
    {
        switch ($unidad) {
            case 'día':
            case 'días':
                return $valor / 30.44;
            case 'semana':
            case 'semanas':
                return $valor / 4.345;
            case 'mes':
            case 'meses':
                return $valor;
            case 'año':
            case 'años':
                return $valor * 12;
            default:
                return $valor; // Asumir meses por defecto
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
     * Método para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoTerapiaId): array
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tipoTerapia = TipoTerapia::findOrFail($tipoTerapiaId);

        return $this->validarMascotaParaTipoTerapia($mascota, $tipoTerapia);
    }
}