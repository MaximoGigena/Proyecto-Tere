<?php
// app/Http/Requests/StoreTerapiaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\TerapiaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreTerapiaRequest extends FormRequest
{
    protected $validationService;

    public function __construct(TerapiaValidationService $validationService)
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
            'tipo_terapia_id' => 'required|exists:tipos_terapia,id',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:diaria,semanal,quincenal,mensual,personalizada',
            'duracion_tratamiento' => 'required|string|max:100',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'evolucion' => 'nullable|in:mejoria,estable,empeoramiento',
            'recomendaciones_tutor' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'costo' => 'nullable|numeric|min:0',
            'archivos.*' => 'nullable|file|max:10240',
            'medio_envio' => 'nullable|string|in:email,whatsapp,telegram'
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_terapia_id.required' => 'Debe seleccionar un tipo de terapia.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'frecuencia.required' => 'La frecuencia es requerida.',
            'duracion_tratamiento.required' => 'La duración del tratamiento es requerida.',
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
                Log::info('Validando terapia', [
                    'route_params' => $this->route()->parameters(),
                    'mascotaId_from_route' => $this->route('mascotaId'),
                    'mascotaId_final' => $mascotaId,
                    'input_all' => $this->all()
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_terapia_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoTerapiaId = $this->input('tipo_terapia_id');

                try {
                    // Asegurar que mascotaId es int
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoTerapiaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_terapia_id', $error);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error en validación de terapia', [
                        'mascota_id' => $mascotaId,
                        'tipo_terapia_id' => $tipoTerapiaId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_terapia_id', 
                        'Error en validación: ' . $e->getMessage()
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
                'message' => 'Error de validación',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}