<?php
// app/Http/Requests/UpdateFarmacoRequest.php

namespace App\Http\Requests;

use App\Models\ProcedimientosMedicos\Farmaco;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\FarmacoValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UpdateFarmacoRequest extends FormRequest
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
     * Validación adicional con lógica de negocio para actualización
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si no hay errores en las reglas básicas
            if (!$validator->errors()->any()) {
                // DEBUG: Ver qué parámetros tiene la ruta
                Log::info('🔍 DEBUG - Parámetros de ruta en UpdateFarmacoRequest', [
                    'route_parameters' => $this->route()->parameters(),
                    'all_routes' => $this->route()->parameterNames(),
                    'full_url' => $this->fullUrl(),
                    'method' => $this->method()
                ]);

                // Obtener mascotaId de diferentes formas
                $mascotaId = $this->route('mascotaId') ?? // Probamos con 'mascotaId'
                             $this->route('mascota') ??    // Probamos con 'mascota'
                             $this->route('mascota_id') ?? // Probamos con 'mascota_id'
                             $this->input('mascota_id');   // Probamos como input

                // DEBUG: Ver qué valor obtenemos
                Log::info('🔍 DEBUG - mascotaId obtenido', [
                    'mascotaId' => $mascotaId,
                    'type' => gettype($mascotaId)
                ]);

                if (empty($mascotaId)) {
                    // Si aún está vacío, intentamos obtenerlo de la URL manualmente
                    $segments = $this->segments();
                    Log::info('🔍 DEBUG - Segmentos de URL', ['segments' => $segments]);
                    
                    // Asumiendo estructura: /api/mascotas/{mascotaId}/farmacos/{farmacoId}
                    // Buscar el segmento después de 'mascotas'
                    foreach ($segments as $index => $segment) {
                        if ($segment === 'mascotas' && isset($segments[$index + 1])) {
                            $mascotaId = $segments[$index + 1];
                            break;
                        }
                    }
                }

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_farmaco_id', 
                        'No se pudo identificar la mascota para la validación. Debug: ' . 
                        json_encode([
                            'route_params' => $this->route()->parameters(),
                            'segments' => $this->segments()
                        ])
                    );
                    return;
                }

                $tipoFarmacoId = $this->input('tipo_farmaco_id');
                $farmacoId = $this->route('farmacoId') ?? 
                            $this->route('farmaco') ?? 
                            $this->route('farmaco_id');

                // DEBUG: Ver todos los IDs
                Log::info('🔍 DEBUG - IDs para validación', [
                    'mascotaId' => $mascotaId,
                    'tipoFarmacoId' => $tipoFarmacoId,
                    'farmacoId' => $farmacoId
                ]);

                try {
                    // Validar que el fármaco existe antes de actualizar
                    $farmaco = Farmaco::find($farmacoId);
                    if (!$farmaco) {
                        $validator->errors()->add(
                            'farmaco_id', 
                            'El fármaco que intenta modificar no existe.'
                        );
                        return;
                    }

                    // Validación cruzada con el servicio
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoFarmacoId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_farmaco_id', $error);
                        }
                    }

                    // Validación adicional: verificar que el fármaco pertenece a la mascota
                    if ($farmaco->procesoMedico->mascota_id != $mascotaId) {
                        $validator->errors()->add(
                            'farmaco_id',
                            'El fármaco no pertenece a la mascota especificada.'
                        );
                    }

                } catch (\Exception $e) {
                    Log::error('Error en validación de actualización de fármaco', [
                        'mascota_id' => $mascotaId,
                        'farmaco_id' => $farmacoId,
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
        // Registrar el error específico para debugging
        Log::error('❌ Error de validación en UpdateFarmacoRequest', [
            'errors' => $validator->errors()->toArray(),
            'route_parameters' => $this->route()->parameters(),
            'input' => $this->all()
        ]);

        throw new ValidationException(
            $validator,
            response()->json([
                'success' => false,
                'message' => 'Error de validación al actualizar el fármaco',
                'errors' => $validator->errors()->toArray(),
                'debug' => [
                    'route_parameters' => $this->route()->parameters(),
                    'mascotaId_detectado' => $this->route('mascotaId') ?? 
                                            $this->route('mascota') ?? 
                                            $this->route('mascota_id')
                ]
            ], 422)
        );
    }
}