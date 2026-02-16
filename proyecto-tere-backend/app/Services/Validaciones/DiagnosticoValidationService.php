<?php
// app/Services/Validaciones/DiagnosticoValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoDiagnostico;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DiagnosticoValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de diagnóstico específico
     */
    public function validarMascotaParaTipoDiagnostico(Mascota $mascota, TipoDiagnostico $tipoDiagnostico): array
    {
        $errors = [];
        $detalles = [];

        // 1. Validar especie
        $especieValida = $this->validarEspecie($mascota, $tipoDiagnostico);
        $detalles['especie_valida'] = $especieValida;
        
        if (!$especieValida) {
            $errors[] = "Este diagnóstico no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar edad (si el diagnóstico tiene restricciones de edad)
        $edadValidation = $this->validarEdad($mascota, $tipoDiagnostico);
        $detalles['edad_valida'] = $edadValidation['valido'];
        $detalles['edad_actual_meses'] = $this->calcularEdadEnMeses($mascota);
        
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar estado de salud (ejemplo: si el diagnóstico requiere ciertas condiciones)
        $saludValidation = $this->validarEstadoSalud($mascota, $tipoDiagnostico);
        if (!$saludValidation['valido']) {
            $errors[] = $saludValidation['mensaje'];
        }

        // 4. Validar criterios de exclusión
        $exclusionValidation = $this->validarCriteriosExclusion($mascota, $tipoDiagnostico);
        if (!$exclusionValidation['valido']) {
            $errors = array_merge($errors, $exclusionValidation['errores']);
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => array_merge($detalles, [
                'mascota_id' => $mascota->id,
                'tipo_diagnostico_id' => $tipoDiagnostico->id,
                'diagnostico_nombre' => $tipoDiagnostico->nombre,
                'mascota_nombre' => $mascota->nombre,
                'mascota_especie' => $mascota->especie,
            ])
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para el diagnóstico
     */
    private function validarEspecie(Mascota $mascota, TipoDiagnostico $tipoDiagnostico): bool
    {
        $especiesPermitidas = $tipoDiagnostico->especies;
        
        // Si es null, vacío o no está definido, acepta todas las especies
        if (empty($especiesPermitidas)) {
            return true;
        }

        // Si es string, intentar decodificar como JSON
        if (is_string($especiesPermitidas)) {
            $decoded = json_decode($especiesPermitidas, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $especiesPermitidas = $decoded;
            } else {
                $especiesPermitidas = [$especiesPermitidas];
            }
        }

        // Verificar si la especie de la mascota está en las permitidas
        $especieMascota = strtolower($mascota->especie);
        $especiesPermitidas = array_map('strtolower', (array)$especiesPermitidas);
        
        return in_array($especieMascota, $especiesPermitidas);
    }

    /**
     * Validar restricciones de edad
     */
    private function validarEdad(Mascota $mascota, TipoDiagnostico $tipoDiagnostico): array
    {
        // Por defecto, no hay restricciones de edad para diagnósticos
        // Pero puedes agregar lógica personalizada aquí
        
        // Ejemplo: Si el diagnóstico es para animales mayores y la mascota es muy joven
        $edadMeses = $this->calcularEdadEnMeses($mascota);
        
        if ($edadMeses === null) {
            return [
                'valido' => true, // Permitir si no se puede calcular la edad
                'mensaje' => ''
            ];
        }

        // Aquí puedes agregar lógica específica
        // Por ejemplo, si el diagnóstico requiere edad mínima basada en el nombre o características
        $nombreDiagnostico = strtolower($tipoDiagnostico->nombre);
        
        // Ejemplo: Si es un diagnóstico de "Artrosis" y la mascota es muy joven
        if (str_contains($nombreDiagnostico, 'artrosis') || str_contains($nombreDiagnostico, 'degenerativo')) {
            if ($edadMeses < 24) { // Menos de 2 años
                return [
                    'valido' => false,
                    'mensaje' => "Este diagnóstico degenerativo generalmente no se aplica a mascotas menores de 2 años."
                ];
            }
        }

        // Ejemplo: Si es un diagnóstico congénito y la mascota es muy adulta
        if (str_contains($nombreDiagnostico, 'congenito') || str_contains($nombreDiagnostico, 'genetico')) {
            if ($edadMeses > 24) { // Más de 2 años
                return [
                    'valido' => true, // Permitir pero con advertencia
                    'mensaje' => "Nota: Este diagnóstico genético generalmente se identifica en animales jóvenes."
                ];
            }
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Validar estado de salud de la mascota
     */
    private function validarEstadoSalud(Mascota $mascota, TipoDiagnostico $tipoDiagnostico): array
    {
        // Aquí puedes implementar lógica para validar el estado de salud
        // Por ejemplo, verificar si la mascota tiene condiciones preexistentes
        
        // Por ahora, retornar válido por defecto
        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Validar criterios de exclusión del diagnóstico
     */
    private function validarCriteriosExclusion(Mascota $mascota, TipoDiagnostico $tipoDiagnostico): array
    {
        $errores = [];
        
        // Verificar criterios de exclusión si existen
        if (!empty($tipoDiagnostico->criterios_exclusion)) {
            $criteriosExclusion = $tipoDiagnostico->criterios_exclusion;
            
            // Si es string, intentar parsear
            if (is_string($criteriosExclusion)) {
                $criteriosArray = explode(', ', $criteriosExclusion);
                
                foreach ($criteriosArray as $criterio) {
                    // Ejemplo: Si el criterio es "no_cachorros" y la mascota es joven
                    if (str_contains(strtolower($criterio), 'no_cachorros') || 
                        str_contains(strtolower($criterio), 'solo_adultos')) {
                        
                        $edadMeses = $this->calcularEdadEnMeses($mascota);
                        if ($edadMeses !== null && $edadMeses < 12) {
                            $errores[] = "Este diagnóstico no aplica para cachorros menores de 1 año.";
                        }
                    }
                    
                    // Ejemplo: Si el criterio es "solo_castrados" y la mascota no está castrada
                    if (str_contains(strtolower($criterio), 'solo_castrados') && 
                        !$mascota->castrado) {
                        $errores[] = "Este diagnóstico requiere que la mascota esté castrada.";
                    }
                    
                    // Agrega más criterios según sea necesario
                }
            }
        }
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
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
     * Método para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoDiagnosticoId): array
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tipoDiagnostico = TipoDiagnostico::findOrFail($tipoDiagnosticoId);

        return $this->validarMascotaParaTipoDiagnostico($mascota, $tipoDiagnostico);
    }

    /**
     * Validar múltiples diagnósticos diferenciales
     */
    public function validarDiagnosticosDiferenciales(int $mascotaId, array $diagnosticosDiferenciales): array
    {
        $errors = [];
        $validaciones = [];
        
        $mascota = Mascota::findOrFail($mascotaId);

        foreach ($diagnosticosDiferenciales as $index => $diferencial) {
            if (!isset($diferencial['id'])) {
                continue;
            }

            $tipoDiagnostico = TipoDiagnostico::find($diferencial['id']);
            if (!$tipoDiagnostico) {
                $errors[] = "Diagnóstico diferencial #" . ($index + 1) . ": Tipo no encontrado.";
                continue;
            }

            $validacion = $this->validarMascotaParaTipoDiagnostico($mascota, $tipoDiagnostico);
            $validaciones[$index] = $validacion;

            if (!$validacion['valido']) {
                foreach ($validacion['errors'] as $error) {
                    $errors[] = "Diagnóstico diferencial '{$tipoDiagnostico->nombre}': " . $error;
                }
            }
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'validaciones' => $validaciones
        ];
    }

    /**
     * Validar si se puede actualizar un diagnóstico existente
     */
    public function validarParaActualizacion(int $diagnosticoId, int $mascotaId, int $nuevoTipoDiagnosticoId): array
    {
        // Aquí podrías agregar lógica adicional para actualizaciones
        // Por ejemplo, verificar si ya existe un diagnóstico similar activo
        
        return $this->validarAntesDeRegistro($mascotaId, $nuevoTipoDiagnosticoId);
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
}