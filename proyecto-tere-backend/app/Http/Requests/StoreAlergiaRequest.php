<?php
// app/Http/Requests/StoreAlergiaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\AlergiaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreAlergiaRequest extends FormRequest
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
            'tipo_alergia_id' => 'required|exists:tipos_alergia,id',
            'fecha_deteccion' => 'required|date',
            'gravedad' => 'required|in:leve,moderada,grave',
            'reaccion_comun' => 'required|string|max:255',
            'estado' => 'required|in:activa,superada,seguimiento',
            'desencadenante' => 'nullable|string|max:255',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'conducta_recomendada' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_alergia_id.required' => 'Debe seleccionar un tipo de alergia.',
            'tipo_alergia_id.exists' => 'El tipo de alergia seleccionado no es válido.',
            'fecha_deteccion.required' => 'La fecha de detección es requerida.',
            'fecha_deteccion.date' => 'La fecha de detección debe ser una fecha válida.',
            'gravedad.required' => 'Debe seleccionar la gravedad de la alergia.',
            'gravedad.in' => 'La gravedad seleccionada no es válida.',
            'reaccion_comun.required' => 'Debe describir la reacción común.',
            'estado.required' => 'Debe seleccionar el estado de la alergia.',
            'medio_envio.required' => 'Debe seleccionar el medio de envío del documento.',
        ];
    }

    /**
     * Validación adicional con lógica de negocio
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si no hay errores en las reglas básicas
            if (!$validator->errors()->any()) {
                // Obtener el mascotaId de la ruta
                $mascotaId = $this->route('mascotaId') ?? $this->route('mascota');
                
                // Si aún es null, intentar obtenerlo del input
                if (is_null($mascotaId)) {
                    $mascotaId = $this->input('mascota_id');
                }
                
                // Log para debug
                Log::info('Validando alergia', [
                    'route_params' => $this->route()->parameters(),
                    'mascotaId_from_route' => $this->route('mascotaId'),
                    'mascotaId_final' => $mascotaId,
                    'tipo_alergia_id' => $this->input('tipo_alergia_id')
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_alergia_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoAlergiaId = $this->input('tipo_alergia_id');

                try {
                    // Validación transitiva mascota -> tipo alergia
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoAlergiaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_alergia_id', $error);
                        }
                        
                        // Agregar detalles de debug en desarrollo
                        if (config('app.debug')) {
                            Log::debug('Detalles de validación fallida', $validacion['detalles']);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error en validación de alergia', [
                        'mascota_id' => $mascotaId,
                        'tipo_alergia_id' => $tipoAlergiaId,
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
                'message' => 'Error de validación para alergia',
                'errors' => $validator->errors()->toArray(),
                'debug' => config('app.debug') ? [
                    'mascota_id' => $this->route('mascotaId'),
                    'tipo_alergia_id' => $this->input('tipo_alergia_id')
                ] : null
            ], 422)
        );
    }
}