<?php
// app/Http/Requests/StoreCirugiaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\CirugiaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreCirugiaRequest extends FormRequest
{
    protected $validationService;

    public function __construct(CirugiaValidationService $validationService)
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
            'tipo_cirugia_id' => 'required|exists:tipos_cirugia,id',
            'fecha_cirugia' => 'required|date',
            'resultado' => 'nullable|string|in:satisfactorio,complicaciones,estable,critico',
            'estado_actual' => 'nullable|string|in:recuperacion,alta,seguimiento,hospitalizado',
            'diagnostico_causa' => 'required|string|max:500',
            'fecha_control_estimada' => 'nullable|date|after:fecha_cirugia',
            'descripcion_procedimiento' => 'nullable|string',
            'medicacion_postquirurgica' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'medio_envio' => 'required|in:email,whatsapp,telegram',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_cirugia_id.required' => 'Debe seleccionar un tipo de cirugía.',
            'tipo_cirugia_id.exists' => 'El tipo de cirugía seleccionado no existe.',
            'fecha_cirugia.required' => 'La fecha de la cirugía es requerida.',
            'fecha_cirugia.date' => 'La fecha debe ser una fecha válida.',
            'diagnostico_causa.required' => 'El diagnóstico que causa la cirugía es requerido.',
            'descripcion_procedimiento.required' => 'La descripción del procedimiento es requerida.',
            'resultado.in' => 'El resultado debe ser uno de: satisfactorio, complicaciones, estable, critico.',
            'estado_actual.in' => 'El estado actual debe ser uno de: recuperacion, alta, seguimiento, hospitalizado.',
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
                // Obtener el mascotaId - ajusta según tu estructura de rutas
                $mascotaId = $this->route('mascotaId') ?? $this->route('mascota');
                
                // Si aún es null, intentar obtenerlo del input
                if (is_null($mascotaId)) {
                    $mascotaId = $this->input('mascota_id');
                }
                
                // Log para debug
                Log::info('Validando cirugía', [
                    'route_params' => $this->route()->parameters(),
                    'mascotaId_final' => $mascotaId,
                    'input_all' => $this->all()
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_cirugia_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoCirugiaId = $this->input('tipo_cirugia_id');

                try {
                    // Validación cruzada: mascota vs tipo de cirugía
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoCirugiaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_cirugia_id', $error);
                        }
                    }

                    // Validación adicional de la cirugía completa
                    $validacionCompleta = $this->validationService->validarCirugiaCompleta([
                        'mascota_id' => $mascotaId,
                        'tipo_cirugia_id' => $tipoCirugiaId,
                        'fecha_cirugia' => $this->input('fecha_cirugia'),
                    ]);

                    if (!$validacionCompleta['valido']) {
                        foreach ($validacionCompleta['errors'] as $error) {
                            $validator->errors()->add('fecha_cirugia', $error);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Error en validación de cirugía', [
                        'mascota_id' => $mascotaId,
                        'tipo_cirugia_id' => $tipoCirugiaId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_cirugia_id', 
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
                'message' => 'Error de validación en la cirugía',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}