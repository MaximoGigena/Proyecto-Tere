<?php
// app/Http/Requests/StoreFarmacoRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\FarmacoValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreFarmacoRequest extends FormRequest
{
    protected $validationService;

    public function __construct(FarmacoValidationService $validationService)
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
            'tipo_farmaco_id' => 'required|exists:tipos_farmaco,id',
            'fecha_administracion' => 'required|date',
            'frecuencia' => 'required|string|max:100',
            'duracion' => 'required|string|max:100',
            'dosis' => 'required|string|max:50',
            'unidad' => 'required|in:mg,ml,UI,comp,gotas',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'proxima_dosis' => 'nullable|date|after:fecha_administracion',
            'reacciones' => 'nullable|string|max:500',
            'recomendaciones' => 'nullable|string|max:500',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
            'archivos.*' => 'nullable|file|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_farmaco_id.required' => 'Debe seleccionar un tipo de fármaco.',
            'fecha_administracion.required' => 'La fecha de administración es requerida.',
            'frecuencia.required' => 'La frecuencia es requerida.',
            'duracion.required' => 'La duración del tratamiento es requerida.',
            'dosis.required' => 'La dosis es requerida.',
            'unidad.required' => 'La unidad de dosis es requerida.',
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

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_farmaco_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoFarmacoId = $this->input('tipo_farmaco_id');

                try {
                    // Asegurar que mascotaId es int
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoFarmacoId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_farmaco_id', $error);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error en validación de fármaco', [
                        'mascota_id' => $mascotaId,
                        'tipo_farmaco_id' => $tipoFarmacoId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_farmaco_id', 
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
                'message' => 'Error de validación en fármaco',
                'errors' => $validator->errors()->toArray(),
                'validation_details' => [
                    'mascota_id' => $this->route('mascotaId'),
                    'tipo_farmaco_id' => $this->input('tipo_farmaco_id')
                ]
            ], 422)
        );
    }
}