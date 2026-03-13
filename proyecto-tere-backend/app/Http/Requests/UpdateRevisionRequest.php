<?php
// app/Http/Requests/UpdateRevisionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\RevisionValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UpdateRevisionRequest extends FormRequest
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
            'medio_envio' => 'nullable|in:email,telegram,whatsapp',
            'archivos_nuevos.*' => 'nullable|file|max:10240',
            'diagnosticos_ids' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_revision_id.required' => 'Debe seleccionar un tipo de revisión.',
            'tipo_revision_id.exists' => 'El tipo de revisión seleccionado no existe.',
            'fecha_revision.required' => 'La fecha de revisión es requerida.',
            'fecha_revision.date' => 'La fecha de revisión debe ser una fecha válida.',
            'nivel_urgencia.required' => 'El nivel de urgencia es requerido.',
            'nivel_urgencia.in' => 'El nivel de urgencia debe ser: rutinaria, preventiva, urgencia o emergencia.',
            'fecha_proxima_revision.after' => 'La próxima revisión debe ser posterior a la fecha actual.',
        ];
    }

    /**
     * Validación adicional con lógica de negocio para UPDATE
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si no hay errores en las reglas básicas
            if (!$validator->errors()->any()) {
                
                // 🔴 CORRECCIÓN: Obtener el mascotaId de la ruta
                // En tu ruta, probablemente es algo como: /api/mascotas/{mascota}/revisiones/{revision}
                $mascotaId = $this->route('mascota'); // Cambiado de 'mascotaId' a 'mascota'
                
                // Si aún es null, intentar con 'mascotaId' (por si acaso)
                if (is_null($mascotaId)) {
                    $mascotaId = $this->route('mascotaId');
                }
                
                // Si sigue siendo null, intentar obtenerlo de los parámetros de la URL
                if (is_null($mascotaId)) {
                    $mascotaId = $this->route()->parameter('mascota');
                }
                
                // Último recurso: obtenerlo del input
                if (is_null($mascotaId)) {
                    $mascotaId = $this->input('mascota_id');
                }
                
                $tipoRevisionId = $this->input('tipo_revision_id');
                $revisionId = $this->route('revision'); // Obtener el ID de la revisión
                
                Log::info('🔍 DEBUG UpdateRevisionRequest', [
                    'mascotaId_obtenido' => $mascotaId,
                    'revisionId' => $revisionId,
                    'tipo_revision_id' => $tipoRevisionId,
                    'rutas_disponibles' => $this->route()->parameters(),
                    'all_inputs' => $this->all()
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'mascota_id', 
                        'No se pudo identificar la mascota para la validación.'
                    );
                    return;
                }

                if (empty($tipoRevisionId)) {
                    $validator->errors()->add(
                        'tipo_revision_id', 
                        'Debe seleccionar un tipo de revisión.'
                    );
                    return;
                }

                try {
                    // 1. Validación de negocio: mascota vs tipo de revisión
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoRevisionId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_revision_id', $error);
                        }
                    }

                    // 2. Validar frecuencia de revisiones (EXCLUYENDO la revisión actual)
                    if ($revisionId) {
                        $frecuenciaValidation = $this->validationService->validarFrecuenciaRevisionUpdate(
                            (int) $mascotaId,
                            (int) $tipoRevisionId,
                            $this->input('fecha_revision'),
                            (int) $revisionId
                        );
                        
                        if (!$frecuenciaValidation['valido']) {
                            foreach ($frecuenciaValidation['errors'] as $error) {
                                $validator->errors()->add('fecha_revision', $error);
                            }
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Error en validación de actualización de revisión', [
                        'mascota_id' => $mascotaId,
                        'revision_id' => $revisionId,
                        'tipo_revision_id' => $tipoRevisionId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'general', 
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

    /**
     * Preparar los datos para la validación
     */
    protected function prepareForValidation()
    {
        // Si los datos vienen como JSON string, decodificarlos
        if ($this->isJson() && empty($this->all())) {
            $data = json_decode($this->getContent(), true);
            if (is_array($data)) {
                $this->merge($data);
            }
        }
    }
}