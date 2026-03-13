<?php
// app/Http/Requests/UpdateAlergiaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\AlergiaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UpdateAlergiaRequest extends FormRequest
{
    protected $validationService;

    public function __construct(AlergiaValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_alergia_id' => 'sometimes|exists:tipos_alergia,id',
            'fecha_deteccion' => 'sometimes|date',
            'gravedad' => 'sometimes|in:leve,moderada,grave',
            'reaccion_comun' => 'sometimes|string|max:255',
            'estado' => 'sometimes|in:activa,superada,seguimiento',
            'desencadenante' => 'nullable|string|max:255',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'conducta_recomendada' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'medio_envio' => 'nullable|in:email,telegram,whatsapp',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_alergia_id.exists' => 'El tipo de alergia seleccionado no es válido.',
            'fecha_deteccion.date' => 'La fecha de detección debe ser una fecha válida.',
            'gravedad.in' => 'La gravedad seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'medio_envio.in' => 'El medio de envío seleccionado no es válido.',
        ];
    }

    /**
     * Validación adicional con lógica de negocio para actualización
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si no hay errores en las reglas básicas
            if (!$validator->errors()->any()) {
                
                // Verificar si se está cambiando el tipo de alergia
                if (!$this->has('tipo_alergia_id')) {
                    return; // No se está cambiando el tipo, no necesitamos validación cruzada
                }

                // OBTENER IDs DE LA RUTA CORRECTAMENTE
                // Los parámetros de ruta están en orden: $mascotaId, $alergiaId
                $routeParameters = $this->route()->parameters();
                
                // Obtener por posición (primer parámetro = mascotaId, segundo = alergiaId)
                $mascotaId = null;
                $alergiaId = null;
                
                // Convertir los parámetros de ruta a un array indexado
                $parameterValues = array_values($routeParameters);
                
                if (count($parameterValues) >= 2) {
                    $mascotaId = $parameterValues[0]; // Primer parámetro = mascotaId
                    $alergiaId = $parameterValues[1]; // Segundo parámetro = alergiaId
                }
                
                // Alternativa: intentar por nombres específicos
                if (!$mascotaId) {
                    $mascotaId = $this->route('mascotaId') ?? $this->route('mascota');
                }
                
                if (!$alergiaId) {
                    $alergiaId = $this->route('alergiaId') ?? $this->route('alergia');
                }

                $nuevoTipoAlergiaId = $this->input('tipo_alergia_id');

                // Log detallado para debug
                Log::info('Validando actualización de alergia', [
                    'route_parameters' => $routeParameters,
                    'mascotaId' => $mascotaId,
                    'alergiaId' => $alergiaId,
                    'nuevo_tipo_alergia_id' => $nuevoTipoAlergiaId,
                    'all_input' => $this->all()
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_alergia_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                if (empty($alergiaId)) {
                    $validator->errors()->add(
                        'tipo_alergia_id', 
                        'No se pudo identificar la alergia para la validación.'
                    );
                    return;
                }

                try {
                    // Validación transitiva para actualización
                    $validacion = $this->validationService->validarAntesDeActualizacion(
                        (int) $mascotaId,
                        (int) $alergiaId,
                        (int) $nuevoTipoAlergiaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_alergia_id', $error);
                        }
                        
                        // Agregar detalles de debug en desarrollo
                        if (config('app.debug')) {
                            Log::debug('Detalles de validación de actualización fallida', $validacion['detalles'] ?? []);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error en validación de actualización de alergia', [
                        'mascota_id' => $mascotaId,
                        'alergia_id' => $alergiaId,
                        'nuevo_tipo_alergia_id' => $nuevoTipoAlergiaId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_alergia_id', 
                        'Error en validación cruzada: ' . $e->getMessage()
                    );
                }
            }
        });
    }

    /**
     * Personalizar la respuesta de error
     */
    protected function failedValidation($validator)
    {
        throw new ValidationException(
            $validator,
            response()->json([
                'success' => false,
                'message' => 'Error de validación al actualizar la alergia',
                'errors' => $validator->errors()->toArray(),
                'debug' => config('app.debug') ? [
                    'mascota_id' => $this->route('mascotaId') ?? $this->route('mascota'),
                    'alergia_id' => $this->route('alergiaId') ?? $this->route('alergia'),
                    'tipo_alergia_id' => $this->input('tipo_alergia_id')
                ] : null
            ], 422)
        );
    }
}