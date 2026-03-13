<?php
// app/Http/Requests/UpdateDesparasitacionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\DesparasitacionValidationService;
use App\Models\ProcedimientosMedicos\Desparasitacion;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UpdateDesparasitacionRequest extends FormRequest
{
    protected $validationService;

    public function __construct(DesparasitacionValidationService $validationService)
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
            'tipo_desparasitacion_id' => 'required|exists:tipos_desparasitacion,id',
            'fecha' => 'required|date',
            'nombre_producto' => 'required|string|max:255',
            'dosis' => 'required|string|max:100',
            'frecuencia_valor' => 'required|integer|min:1',
            'frecuencia_unidad' => 'required|in:dias,semanas,meses',
            'peso' => 'nullable|numeric|min:0',
            'proxima_fecha' => 'nullable|date|after:fecha',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_desparasitacion_id.required' => 'Debe seleccionar un tipo de desparasitación.',
            'fecha.required' => 'La fecha de aplicación es requerida.',
            'nombre_producto.required' => 'El nombre del producto es requerido.',
            'dosis.required' => 'La dosis es requerida.',
            'frecuencia_valor.required' => 'El valor de frecuencia es requerido.',
            'frecuencia_unidad.required' => 'La unidad de frecuencia es requerida.',
            'frecuencia_valor.min' => 'El valor de frecuencia debe ser mayor a 0.',
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
                try {
                    // Obtener la desparasitación que se está actualizando
                    $desparasitacion = Desparasitacion::with('procesoMedico')->find($this->route('id'));
                    
                    if (!$desparasitacion || !$desparasitacion->procesoMedico) {
                        $validator->errors()->add(
                            'tipo_desparasitacion_id', 
                            'No se pudo identificar la mascota para la validación.'
                        );
                        return;
                    }

                    $mascotaId = $desparasitacion->procesoMedico->mascota_id;
                    $tipoDesparasitacionId = $this->input('tipo_desparasitacion_id');

                    // Log para debug
                    Log::info('Validando actualización de desparasitación', [
                        'desparasitacion_id' => $this->route('id'),
                        'mascota_id' => $mascotaId,
                        'tipo_desparasitacion_id' => $tipoDesparasitacionId,
                        'tipo_anterior' => $desparasitacion->tipo_desparasitacion_id
                    ]);

                    // Validar compatibilidad mascota-tipo desparasitación
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoDesparasitacionId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_desparasitacion_id', $error);
                        }
                    }

                    // Validación adicional de frecuencia (opcional)
                    $frecuenciaValidation = $this->validationService->validarFrecuenciaRecomendada(
                        (int) $tipoDesparasitacionId,
                        (int) $this->input('frecuencia_valor'),
                        $this->input('frecuencia_unidad')
                    );

                    if (!$frecuenciaValidation['valido']) {
                        foreach ($frecuenciaValidation['errors'] as $error) {
                            $validator->errors()->add('frecuencia_valor', $error);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Error en validación de actualización de desparasitación', [
                        'desparasitacion_id' => $this->route('id'),
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_desparasitacion_id', 
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