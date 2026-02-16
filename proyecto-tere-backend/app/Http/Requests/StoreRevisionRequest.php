<?php
// app/Http/Requests/StoreRevisionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\RevisionValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreRevisionRequest extends FormRequest
{
    protected $validationService;

    public function __construct(RevisionValidationService $validationService)
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
            'tipo_revision_id' => 'required|exists:tipos_revision,id',
            'fecha_revision' => 'required|date',
            'nivel_urgencia' => 'required|in:rutinaria,preventiva,urgencia,emergencia',
            'motivo_consulta' => 'nullable|string|max:500',
            'diagnostico' => 'nullable|string|max:500',
            'fecha_proxima_revision' => 'nullable|date|after:fecha_revision',
            'indicaciones_medicas' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
            'archivos.*' => 'nullable|file|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_revision_id.required' => 'Debe seleccionar un tipo de revisión.',
            'fecha_revision.required' => 'La fecha de revisión es requerida.',
            'nivel_urgencia.required' => 'El nivel de urgencia es requerido.',
            'nivel_urgencia.in' => 'El nivel de urgencia debe ser uno de los valores permitidos.',
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
                
                Log::info('Validando revisión', [
                    'mascotaId_final' => $mascotaId,
                    'tipo_revision_id' => $this->input('tipo_revision_id')
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_revision_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                $tipoRevisionId = $this->input('tipo_revision_id');

                try {
                    // Validación de negocio: mascota vs tipo de revisión
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoRevisionId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_revision_id', $error);
                        }
                    }

                    // Validar frecuencia de revisiones (opcional, si quieres evitar revisiones muy seguidas)
                    $frecuenciaValidation = $this->validationService->validarFrecuenciaRevision(
                        (int) $mascotaId,
                        (int) $tipoRevisionId,
                        $this->input('fecha_revision')
                    );
                    
                    if (!$frecuenciaValidation['valido']) {
                        foreach ($frecuenciaValidation['errors'] as $error) {
                            $validator->errors()->add('fecha_revision', $error);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Error en validación de revisión', [
                        'mascota_id' => $mascotaId,
                        'tipo_revision_id' => $tipoRevisionId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_revision_id', 
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