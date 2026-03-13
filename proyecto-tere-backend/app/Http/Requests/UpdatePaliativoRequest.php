<?php
// app/Http/Requests/UpdatePaliativoRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\PaliativoValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UpdatePaliativoRequest extends FormRequest
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
            'tipo_procedimiento_id' => 'sometimes|exists:tipos_paliativo,id',
            'fecha_inicio' => 'sometimes|date',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'resultado' => 'sometimes|in:mejoria,alivio,estabilizacion,sin_cambio,empeoramiento',
            'estado' => 'sometimes|in:estable,dolor_controlado,dolor_parcial,deterioro,critico',
            'frecuencia_valor' => 'nullable|integer|min:1',
            'frecuencia_unidad' => 'nullable|in:horas,dias,semanas,meses',
            'fecha_control' => 'nullable|date',
            'descripcion' => 'nullable|string|max:1000',
            'medicacion_notas' => 'nullable|string|max:500',
            'recomendaciones' => 'nullable|string|max:500',
            
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
            'archivos_a_eliminar' => 'nullable|array',
            'archivos_a_eliminar.*' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_procedimiento_id.exists' => 'El tipo de procedimiento seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'resultado.in' => 'El resultado seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }

    /**
     * Validación adicional con lógica de negocio
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si se está cambiando el tipo de procedimiento
            if (!$validator->errors()->any() && $this->has('tipo_procedimiento_id')) {
                
                // CORRECCIÓN: Obtener el ID de la mascota de diferentes fuentes posibles
                $mascotaId = $this->obtenerMascotaId();
                
                // CORRECCIÓN: Obtener el ID del paliativo de los parámetros de ruta
                $paliativoId = $this->route('paliativo') ?? $this->route('paliativoId');
                
                Log::info('Validando actualización de procedimiento paliativo', [
                    'route_params' => $this->route()->parameters(),
                    'mascotaId_encontrado' => $mascotaId,
                    'paliativoId' => $paliativoId,
                    'tipo_procedimiento_id' => $this->input('tipo_procedimiento_id'),
                    'url' => $this->url(),
                    'method' => $this->method()
                ]);

                if (empty($mascotaId)) {
                    $validator->errors()->add(
                        'tipo_procedimiento_id', 
                        'No se pudo identificar la mascota para la validación. Verifique la URL o los datos enviados.'
                    );
                    return;
                }

                if (empty($paliativoId)) {
                    $validator->errors()->add(
                        'tipo_procedimiento_id', 
                        'No se pudo identificar el procedimiento paliativo a actualizar.'
                    );
                    return;
                }

                $nuevoTipoPaliativoId = $this->input('tipo_procedimiento_id');

                try {
                    // Usar el método que acepta el ID del paliativo actual
                    $validacion = $this->validationService->validarAntesDeActualizar(
                        (int) $mascotaId, 
                        (int) $nuevoTipoPaliativoId,
                        (int) $paliativoId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_procedimiento_id', $error);
                        }
                    }

                    // Agregar advertencias como mensajes informativos
                    if (!empty($validacion['advertencias'])) {
                        foreach ($validacion['advertencias'] as $advertencia) {
                            Log::warning('Advertencia en validación de actualización de paliativo', [
                                'mascota_id' => $mascotaId,
                                'paliativo_id' => $paliativoId,
                                'advertencia' => $advertencia
                            ]);
                        }
                        
                        // Guardar advertencias en la sesión para mostrarlas al usuario
                        session()->flash('paliativo_advertencias', $validacion['advertencias']);
                    }

                    // Agregar detalles de validación a la solicitud
                    $this->merge([
                        'validacion_detalles' => $validacion['detalles']
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error en validación de actualización de paliativo', [
                        'mascota_id' => $mascotaId,
                        'paliativo_id' => $paliativoId,
                        'nuevo_tipo_id' => $nuevoTipoPaliativoId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_procedimiento_id', 
                        'Error en validación: ' . $e->getMessage()
                    );
                }
            }

            // Validación adicional: si se está cambiando el resultado, verificar consistencia
            if ($this->has('resultado') && $this->has('estado')) {
                $this->validarConsistenciaResultadoEstado($validator);
            }
        });
    }

    /**
     * Obtener el ID de la mascota de diferentes fuentes posibles
     */
    private function obtenerMascotaId()
    {
        // Intentar obtener de los parámetros de ruta
        $mascotaId = $this->route('mascotaId') ?? $this->route('mascota') ?? $this->route('mascota_id');
        
        // Si no está en la ruta, intentar del input
        if (is_null($mascotaId)) {
            $mascotaId = $this->input('mascota_id');
        }
        
        // Si aún es null, intentar obtener del paliativo existente
        if (is_null($mascotaId)) {
            $paliativoId = $this->route('paliativo') ?? $this->route('paliativoId');
            if ($paliativoId) {
                try {
                    $paliativo = \App\Models\ProcedimientosMedicos\CuidadoPaliativo::with('procesoMedico')->find($paliativoId);
                    if ($paliativo && $paliativo->procesoMedico) {
                        $mascotaId = $paliativo->procesoMedico->mascota_id;
                        Log::info('Mascota ID obtenida del paliativo existente', [
                            'paliativo_id' => $paliativoId,
                            'mascota_id' => $mascotaId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('No se pudo obtener mascota_id del paliativo', [
                        'paliativo_id' => $paliativoId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $mascotaId;
    }

    /**
     * Validar consistencia entre resultado y estado
     */
    private function validarConsistenciaResultadoEstado($validator)
    {
        $resultado = $this->input('resultado');
        $estado = $this->input('estado');

        $inconsistencias = [
            'mejoria' => ['deterioro', 'critico'],
            'empeoramiento' => ['estable', 'dolor_controlado'],
        ];

        if (isset($inconsistencias[$resultado]) && in_array($estado, $inconsistencias[$resultado])) {
            $validator->errors()->add(
                'resultado',
                "El resultado '{$resultado}' es inconsistente con el estado '{$estado}' de la mascota."
            );
        }
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
                'message' => 'Error de validación en la actualización',
                'errors' => $validator->errors()->toArray(),
                'type' => 'paliativo_update_validation'
            ], 422)
        );
    }
}