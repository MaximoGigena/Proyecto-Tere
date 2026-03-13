<?php
// app/Http/Requests/UpdateDiagnosticoRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\DiagnosticoValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UpdateDiagnosticoRequest extends FormRequest
{
    protected $validationService;

    public function __construct(DiagnosticoValidationService $validationService)
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
            'tipo_diagnostico_id' => 'required|exists:tipos_diagnostico,id',
            'nombre' => 'required|string|max:255',
            'fecha_diagnostico' => 'required|date',
            'estado' => 'required|in:activo,resuelto,cronico,seguimiento,sospecha',
            'examenes' => 'nullable|string',
            'conducta' => 'nullable|string',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'costo' => 'nullable|numeric|min:0',
            'archivos.*' => 'nullable|file|max:10240',
            'archivos_a_eliminar' => 'nullable|array',
            'archivos_a_eliminar.*' => 'string',
            'medio_envio' => 'nullable|in:email,telegram,whatsapp',
            'diagnosticos_diferenciales_seleccionados' => 'nullable|array',
            'diagnosticos_diferenciales_seleccionados.*.id' => 'required|exists:tipos_diagnostico,id',
            'diagnosticos_diferenciales_seleccionados.*.nombre' => 'required|string|max:255',
            'diagnosticos_diferenciales_seleccionados.*.relevancia' => 'nullable|in:alta,media,baja',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_diagnostico_id.required' => 'Debe seleccionar un tipo de diagnóstico.',
            'nombre.required' => 'El nombre del diagnóstico es requerido.',
            'fecha_diagnostico.required' => 'La fecha de diagnóstico es requerida.',
            'estado.required' => 'El estado del diagnóstico es requerido.',
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
                $mascotaId = $this->route('mascotaId');
                
                // Si es null, intentar obtenerlo del input
                if (is_null($mascotaId)) {
                    $mascotaId = $this->input('mascota_id');
                }
                
                $tipoDiagnosticoId = $this->input('tipo_diagnostico_id');
                
                // Log para debug
                Log::info('Validando actualización de diagnóstico', [
                    'mascota_id' => $mascotaId,
                    'diagnostico_id' => $this->route('diagnosticoId'),
                    'tipo_diagnostico_id' => $tipoDiagnosticoId
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_diagnostico_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                try {
                    // Validar mascota vs tipo de diagnóstico
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoDiagnosticoId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_diagnostico_id', $error);
                        }
                    }

                    // Validar también los diagnósticos diferenciales si existen
                    $diferenciales = $this->input('diagnosticos_diferenciales_seleccionados', []);
                    if (is_array($diferenciales) && count($diferenciales) > 0) {
                        $validacionDiferenciales = $this->validationService->validarDiagnosticosDiferenciales(
                            (int) $mascotaId,
                            $diferenciales
                        );

                        if (!$validacionDiferenciales['valido']) {
                            foreach ($validacionDiferenciales['errors'] as $error) {
                                $validator->errors()->add('diagnosticos_diferenciales_seleccionados', $error);
                            }
                        }
                    }

                    // Validación adicional específica para actualización
                    $diagnosticoId = $this->route('diagnosticoId');
                    $validacionActualizacion = $this->validationService->validarParaActualizacion(
                        (int) $diagnosticoId,
                        (int) $mascotaId,
                        (int) $tipoDiagnosticoId
                    );

                    if (!$validacionActualizacion['valido']) {
                        foreach ($validacionActualizacion['errors'] as $error) {
                            $validator->errors()->add('tipo_diagnostico_id', $error);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Error en validación de actualización de diagnóstico', [
                        'mascota_id' => $mascotaId,
                        'diagnostico_id' => $this->route('diagnosticoId'),
                        'tipo_diagnostico_id' => $tipoDiagnosticoId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_diagnostico_id', 
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
                'message' => 'Error de validación al actualizar el diagnóstico',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }

    /**
     * Preparar los datos para la validación
     */
    protected function prepareForValidation()
    {
        // Procesar diagnósticos diferenciales si vienen como JSON string
        if ($this->has('diagnosticos_diferenciales_seleccionados') && 
            is_string($this->diagnosticos_diferenciales_seleccionados)) {
            
            try {
                $decoded = json_decode($this->diagnosticos_diferenciales_seleccionados, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge([
                        'diagnosticos_diferenciales_seleccionados' => $decoded
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Error decodificando JSON en prepareForValidation', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}