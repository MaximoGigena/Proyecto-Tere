<?php
// app/Http/Requests/UpdateTerapiaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\TerapiaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\ProcedimientosMedicos\Terapia;

class UpdateTerapiaRequest extends FormRequest
{
    protected $validationService;

    public function __construct(TerapiaValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Log para ver si se ejecutan las reglas
        Log::info('📋 UpdateTerapiaRequest - Reglas de validación', [
            'all_data' => $this->all(),
            'route_params' => $this->route()?->parameters(),
            'uri' => $this->getRequestUri(),
            'method' => $this->method()
        ]);

        return [
            'tipo_terapia_id' => 'required|exists:tipos_terapia,id',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:diaria,semanal,quincenal,mensual,personalizada',
            'duracion_tratamiento' => 'required|string|max:100',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'evolucion' => 'nullable|in:mejoria,estable,empeoramiento',
            'recomendaciones_tutor' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'costo' => 'nullable|numeric|min:0',
            'medio_envio' => 'nullable|string|in:email,whatsapp,telegram',
        ];
    }

    /**
     * Prepara los datos para la validación
     */
    protected function prepareForValidation()
    {
        Log::info('🔄 UpdateTerapiaRequest - prepareForValidation', [
            'route_params' => $this->route()?->parameters(),
            'all_input' => $this->all()
        ]);
    }

    /**
     * Validación adicional con lógica de negocio
     */
    public function withValidator($validator)
    {
        Log::info('🚀 UpdateTerapiaRequest - withValidator iniciado', [
            'validator_errors' => $validator->errors()->any(),
            'route_params' => $this->route()?->parameters()
        ]);

        $validator->after(function ($validator) {
            Log::info('📝 UpdateTerapiaRequest - after validator ejecutándose');
            
            // Solo validar si no hay errores en las reglas básicas
            if (!$validator->errors()->any()) {
                
                Log::info('🔍 UpdateTerapiaRequest - Buscando terapia', [
                    'route_params' => $this->route()?->parameters(),
                    'all_params' => $this->route()?->parameters(),
                    'terapiaId_por_route_terapiaId' => $this->route('terapiaId'),
                    'terapiaId_por_route_terapia' => $this->route('terapia'),
                    'terapiaId_por_route_id' => $this->route('id'),
                    'terapiaId_por_route_terapia_id' => $this->route('terapia_id'),
                    'url' => $this->url(),
                    'method' => $this->method(),
                    'full_url' => $this->fullUrl()
                ]);
                
                // Intentar obtener el ID de todas las formas posibles
                $terapiaId = $this->route('terapiaId') 
                          ?? $this->route('terapia') 
                          ?? $this->route('id')
                          ?? $this->route('terapia_id')
                          ?? $this->input('terapia_id') // Por si viene en el body
                          ?? $this->input('id');
                
                if (!$terapiaId) {
                    // Si aún no lo encuentra, intentar extraer de la URL
                    $url = $this->url();
                    if (preg_match('/\/terapias\/(\d+)/', $url, $matches)) {
                        $terapiaId = $matches[1];
                        Log::info('✅ UpdateTerapiaRequest - ID extraído de URL', ['id' => $terapiaId]);
                    }
                }
                
                Log::info('🔍 UpdateTerapiaRequest - ID final', ['terapiaId' => $terapiaId]);
                
                if (!$terapiaId) {
                    $validator->errors()->add(
                        'tipo_terapia_id', 
                        'No se pudo identificar el ID de la terapia. Parámetros: ' . json_encode($this->route()?->parameters())
                    );
                    return;
                }

                // Buscar la terapia con su proceso médico
                try {
                    $terapia = Terapia::with('procesoMedico')->find($terapiaId);
                    
                    Log::info('🔍 UpdateTerapiaRequest - Resultado búsqueda', [
                        'terapia_encontrada' => $terapia ? 'sí' : 'no',
                        'terapia_id' => $terapiaId
                    ]);
                    
                    if (!$terapia) {
                        $validator->errors()->add(
                            'tipo_terapia_id', 
                            "Terapia con ID {$terapiaId} no encontrada."
                        );
                        return;
                    }
                    
                    if (!$terapia->procesoMedico) {
                        $validator->errors()->add(
                            'tipo_terapia_id', 
                            'La terapia no tiene un proceso médico asociado.'
                        );
                        return;
                    }

                    // Obtener la mascota a través del proceso médico de la terapia
                    $mascotaId = $terapia->procesoMedico->mascota_id;
                    $tipoTerapiaId = $this->input('tipo_terapia_id');

                    Log::info('✅ UpdateTerapiaRequest - Validando', [
                        'terapia_id' => $terapiaId,
                        'mascota_id' => $mascotaId,
                        'tipo_terapia_id' => $tipoTerapiaId
                    ]);

                    // Ejecutar la validación de negocio
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId, 
                        (int) $tipoTerapiaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_terapia_id', $error);
                        }
                    }
                    
                } catch (\Exception $e) {
                    Log::error('❌ Error en validación de actualización de terapia', [
                        'terapia_id' => $terapiaId ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_terapia_id', 
                        'Error en validación: ' . $e->getMessage()
                    );
                }
            } else {
                Log::info('⚠️ UpdateTerapiaRequest - Hay errores de validación previos', [
                    'errors' => $validator->errors()->toArray()
                ]);
            }
        });
    }

    /**
     * Personalizar la respuesta de error
     */
    protected function failedValidation($validator)
    {
        Log::error('❌ UpdateTerapiaRequest - Validación fallida', [
            'errors' => $validator->errors()->toArray(),
            'route_params' => $this->route()?->parameters()
        ]);

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