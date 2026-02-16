<?php
// app/Http/Requests/StoreVacunaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\VacunaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreVacunaRequest extends FormRequest
{
    protected $validationService;

    public function __construct(VacunaValidationService $validationService)
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
            'tipo_vacuna_id' => 'required|exists:tipos_vacuna,id',
            'fecha_aplicacion' => 'required|date',
            'numero_dosis' => 'required|string|max:50',
            'lote_serie' => 'required|string|max:100',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'fecha_proxima_dosis' => 'nullable|date|after:fecha_aplicacion',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_vacuna_id.required' => 'Debe seleccionar un tipo de vacuna.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es requerida.',
            'numero_dosis.required' => 'El número de dosis es requerido.',
            'lote_serie.required' => 'El lote/serie es requerido.',
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
                // Obtener el mascotaId de la ruta - CORRECCIÓN AQUÍ
                $mascotaId = $this->route('mascotaId') ?? $this->route('mascota');
                
                // Si aún es null, intentar obtenerlo del input
                if (is_null($mascotaId)) {
                    $mascotaId = $this->input('mascota_id');
                }
                
                // Log para debug
                Log::info('Validando vacuna', [
                    'route_params' => $this->route()->parameters(),
                    'mascotaId_from_route' => $this->route('mascotaId'),
                    'mascotaId_final' => $mascotaId,
                    'input_all' => $this->all()
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_vacuna_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoVacunaId = $this->input('tipo_vacuna_id');

                try {
                    // Asegurar que mascotaId es int
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoVacunaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_vacuna_id', $error);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error en validación de vacuna', [
                        'mascota_id' => $mascotaId,
                        'tipo_vacuna_id' => $tipoVacunaId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_vacuna_id', 
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