<?php
// app/Http/Requests/StorePaliativoRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\PaliativoValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StorePaliativoRequest extends FormRequest
{
    protected $validationService;

    public function __construct(PaliativoValidationService $validationService)
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
            'tipo_procedimiento_id' => 'required|exists:tipos_paliativo,id',
            'fecha_inicio' => 'required|date',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'resultado' => 'required|in:mejoria,alivio,estabilizacion,sin_cambio,empeoramiento',
            'estado' => 'required|in:estable,dolor_controlado,dolor_parcial,deterioro,critico',
            'frecuencia_valor' => 'nullable|integer|min:1',
            'frecuencia_unidad' => 'nullable|in:horas,dias,semanas,meses',
            'fecha_control' => 'nullable|date',
            'descripcion' => 'nullable|string|max:1000',
            'medicacion_notas' => 'nullable|string|max:500',
            'recomendaciones' => 'nullable|string|max:500',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
            
            'diagnosticos' => 'nullable|array',
            'diagnosticos.*.id' => 'required_with:diagnosticos',
            'diagnosticos.*.type' => 'required_with:diagnosticos|in:App\Models\TiposProcedimientos\TipoDiagnostico,App\Models\ProcedimientosMedicos\Diagnostico',
            
            'farmacos_asociados' => 'nullable|array',
            'farmacos_asociados.*.farmaco_id' => 'required_with:farmacos_asociados|exists:tipos_farmaco,id',
            'farmacos_asociados.*.dosis' => 'required_with:farmacos_asociados|numeric|min:0.001',
            'farmacos_asociados.*.frecuencia' => 'nullable|string',
            'farmacos_asociados.*.duracion' => 'nullable|string',
            'farmacos_asociados.*.observaciones' => 'nullable|string|max:1000',
            'farmacos_asociados.*.momento_aplicacion' => 'required_with:farmacos_asociados|in:inicio,mantenimiento,rescue,final',
            
            'archivos.*' => 'nullable|file|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_procedimiento_id.required' => 'Debe seleccionar un tipo de procedimiento paliativo.',
            'tipo_procedimiento_id.exists' => 'El tipo de procedimiento seleccionado no es válido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'resultado.required' => 'El resultado esperado es requerido.',
            'estado.required' => 'El estado actual de la mascota es requerido.',
            'medio_envio.required' => 'El medio de envío del certificado es requerido.',
        ];
    }

    /**
     * Validación adicional con lógica de negocio
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->any()) {
                $mascotaId = $this->route('mascotaId') ?? $this->route('mascota');
                
                if (is_null($mascotaId)) {
                    $mascotaId = $this->input('mascota_id');
                }

                Log::info('Validando procedimiento paliativo', [
                    'route_params' => $this->route()->parameters(),
                    'mascotaId' => $mascotaId,
                    'tipo_procedimiento_id' => $this->input('tipo_procedimiento_id')
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_procedimiento_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoPaliativoId = $this->input('tipo_procedimiento_id');

                try {
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoPaliativoId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_procedimiento_id', $error);
                        }
                    }

                    // Agregar advertencias como mensajes informativos (no errores)
                    if (!empty($validacion['advertencias'])) {
                        foreach ($validacion['advertencias'] as $advertencia) {
                            // Podrías agregar esto a una sesión flash o respuesta especial
                            Log::warning('Advertencia en validación de paliativo', [
                                'mascota_id' => $mascotaId,
                                'advertencia' => $advertencia
                            ]);
                        }
                    }

                    // Agregar detalles de validación a la solicitud para uso posterior
                    $this->merge([
                        'validacion_detalles' => $validacion['detalles']
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error en validación de procedimiento paliativo', [
                        'mascota_id' => $mascotaId,
                        'tipo_paliativo_id' => $tipoPaliativoId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_procedimiento_id', 
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
                'type' => 'paliativo_validation'
            ], 422)
        );
    }
}