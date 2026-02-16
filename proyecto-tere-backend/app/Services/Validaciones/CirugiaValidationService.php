<?php
// app/Services/Validaciones/CirugiaValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoCirugia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CirugiaValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de cirugía específico
     */
    public function validarMascotaParaTipoCirugia(Mascota $mascota, TipoCirugia $tipoCirugia): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoCirugia)) {
            $errors[] = "Esta cirugía no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar edad mínima (si existe restricción en el tipo de cirugía)
        $edadValidation = $this->validarEdad($mascota, $tipoCirugia);
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar estado de castración (si aplica)
        // Podrías agregar un campo 'requiere_castrado' en TipoCirugia si necesitas esta validación
        if (isset($tipoCirugia->requiere_castrado) && $tipoCirugia->requiere_castrado && !$mascota->castrado) {
            $errors[] = "Esta cirugía requiere que la mascota esté castrada.";
        }

        // 4. Validar otras condiciones médicas preexistentes
        $condicionesErrors = $this->validarCondicionesMedicas($mascota, $tipoCirugia);
        $errors = array_merge($errors, $condicionesErrors);

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_cirugia_id' => $tipoCirugia->id,
                'especie_valida' => $this->validarEspecie($mascota, $tipoCirugia),
                'edad_valida' => $edadValidation['valido'],
                'edad_actual_meses' => $this->calcularEdadEnMeses($mascota),
                'especie_mascota' => $mascota->especie,
                'especies_permitidas' => $tipoCirugia->especies
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para la cirugía
     */
    private function validarEspecie(Mascota $mascota, TipoCirugia $tipoCirugia): bool
    {
        $especiesPermitidas = $tipoCirugia->especies;
        
        // Si es null, vacío o contiene "todos", acepta todas las especies
        if (empty($especiesPermitidas)) {
            return true;
        }

        // Si es string, decodificar desde JSON o convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Si es array, verificar si contiene "todos"
        if (is_array($especiesPermitidas) && in_array('todos', $especiesPermitidas)) {
            return true;
        }

        // Verificar si la especie de la mascota está en las permitidas
        return in_array(strtolower($mascota->especie), 
                       array_map('strtolower', $especiesPermitidas));
    }

    /**
     * Validar edad según restricciones del tipo de cirugía
     * Puedes extender esto para incluir edad máxima también
     */
    private function validarEdad(Mascota $mascota, TipoCirugia $tipoCirugia): array
    {
        // Por ahora, validaciones básicas de edad
        // Puedes agregar campos específicos en TipoCirugia si necesitas más control:
        // - edad_minima, edad_unidad, edad_maxima, etc.
        
        $edadMeses = $this->calcularEdadEnMeses($mascota);
        
        if ($edadMeses === null) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede determinar la edad de la mascota para validar la cirugía.'
            ];
        }

        // Ejemplo: Si la cirugía es para esterilización y la mascota es muy joven
        // (esto es solo un ejemplo, ajusta según tus necesidades)
        if (strpos(strtolower($tipoCirugia->nombre), 'esterilización') !== false) {
            if ($edadMeses < 6 && in_array(strtolower($mascota->especie), ['felino', 'canino'])) {
                $edadFormateada = $this->formatearMesesAHumano($edadMeses);
                return [
                    'valido' => false,
                    'mensaje' => "La mascota tiene {$edadFormateada}, pero la esterilización generalmente requiere mínimo 6 meses para {$mascota->especie}s."
                ];
            }
        }

        // Ejemplo específico para equinos > 6 meses
        if (strtolower($mascota->especie) === 'equino' && $edadMeses < 6) {
            $edadFormateada = $this->formatearMesesAHumano($edadMeses);
            return [
                'valido' => false,
                'mensaje' => "La cirugía '{$tipoCirugia->nombre}' requiere equinos mayores a 6 meses. La mascota tiene {$edadFormateada}."
            ];
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Validar condiciones médicas preexistentes
     */
    private function validarCondicionesMedicas(Mascota $mascota, TipoCirugia $tipoCirugia): array
    {
        $errors = [];

        // Aquí puedes agregar validaciones basadas en:
        // 1. Procesos médicos activos de la mascota
        // 2. Historial de cirugías previas
        // 3. Condiciones crónicas registradas en caracteristicas_mascota


        // Ejemplo: Validar riesgos específicos mencionados en el tipo de cirugía
        if (isset($tipoCirugia->riesgos)) {
            // Puedes analizar los riesgos y verificar si aplican a esta mascota
            // Por ejemplo, si la cirugía menciona riesgos para animales cardíacos
            // y la mascota tiene historial cardíaco
        }

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

            // Parsear fecha desde string dd/mm/yyyy (basado en tu modelo)
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
            Log::error('Error calculando edad en meses para cirugía', [
                'mascota_id' => $mascota->id,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'error' => $e->getMessage()
            ]);
            return null;
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
            $mesesRedondeados = round($meses, 1);
            return "{$mesesRedondeados} mes" . ($mesesRedondeados !== 1 ? 'es' : '');
        } else {
            $años = floor($meses / 12);
            $mesesRestantes = $meses % 12;
            
            if ($mesesRestantes < 1) {
                return "{$años} año" . ($años !== 1 ? 's' : '');
            } else {
                $mesesRestantesRedondeados = round($mesesRestantes, 1);
                return "{$años} año" . ($años !== 1 ? 's' : '') . 
                       " y {$mesesRestantesRedondeados} mes" . ($mesesRestantesRedondeados !== 1 ? 'es' : '');
            }
        }
    }

    /**
     * Método para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoCirugiaId): array
    {
        $mascota = Mascota::with('procesosMedicos')->findOrFail($mascotaId);
        $tipoCirugia = TipoCirugia::findOrFail($tipoCirugiaId);

        return $this->validarMascotaParaTipoCirugia($mascota, $tipoCirugia);
    }

    /**
     * Validar cirugía completa (incluyendo fecha y otros detalles)
     */
    public function validarCirugiaCompleta(array $datosCirugia): array
    {
        $errors = [];

        // Validar mascota y tipo de cirugía
        if (isset($datosCirugia['mascota_id']) && isset($datosCirugia['tipo_cirugia_id'])) {
            $validacion = $this->validarAntesDeRegistro(
                (int) $datosCirugia['mascota_id'],
                (int) $datosCirugia['tipo_cirugia_id']
            );
            
            if (!$validacion['valido']) {
                $errors = array_merge($errors, $validacion['errors']);
            }
        }

        // Validar fecha de cirugía (no puede ser en el pasado lejano o futuro irrazonable)
        if (isset($datosCirugia['fecha_cirugia'])) {
            $fechaCirugia = Carbon::parse($datosCirugia['fecha_cirugia']);
            $hoy = Carbon::now();
            
            if ($fechaCirugia->isBefore($hoy->subMonths(3))) {
                $errors[] = "La fecha de cirugía no puede ser hace más de 3 meses.";
            }
            
            if ($fechaCirugia->isAfter($hoy->addYears(1))) {
                $errors[] = "No se pueden programar cirugías con más de 1 año de anticipación.";
            }
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors
        ];
    }
}