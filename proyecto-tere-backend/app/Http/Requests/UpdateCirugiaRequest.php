<?php
// app/Http/Requests/UpdateCirugiaRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Validaciones\CirugiaValidationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\ProcedimientosMedicos\Cirugia;
use Carbon\Carbon;

class UpdateCirugiaRequest extends FormRequest
{
    protected $validationService;
    protected $cirugia;

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
            'tipo_cirugia_id' => 'sometimes|exists:tipos_cirugia,id',
            'fecha' => 'sometimes|date',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'resultado' => 'sometimes|in:satisfactorio,complicaciones,estable,critico',
            'estado' => 'sometimes|in:recuperacion,alta,seguimiento,hospitalizado',
            'fecha_control' => 'nullable|date',
            'descripcion' => 'nullable|string|max:1000',
            'medicacion_postquirurgica' => 'nullable|string',
            'recomendaciones' => 'nullable|string|max:500',
            'farmacos_asociados' => 'nullable|array',
            'farmacos_asociados.*.farmaco_id' => 'required_with:farmacos_asociados|exists:tipos_farmaco,id',
            'farmacos_asociados.*.dosis' => 'required_with:farmacos_asociados|numeric|min:0.001',
            'farmacos_asociados.*.frecuencia' => 'nullable|string',
            'farmacos_asociados.*.duracion' => 'nullable|string',
            'farmacos_asociados.*.observaciones' => 'nullable|string|max:1000',
            'farmacos_asociados.*.etapa_aplicacion' => 'required_with:farmacos_asociados|in:prequirurgica,transquirurgica,postquirurgica_inmediata,postquirurgica_tardia',
            'farmacos_asociados.*.es_dosis_unica' => 'boolean',
            'archivos.*' => 'nullable|file|max:10240',
            'archivos_a_eliminar' => 'nullable|array',
            'archivos_a_eliminar.*' => 'string',
            'diagnosticos' => 'nullable|array',
            'diagnosticos.*' => 'exists:tipos_diagnostico,id',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_cirugia_id.exists' => 'El tipo de cirugía seleccionado no existe.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'resultado.in' => 'El resultado debe ser uno de: satisfactorio, complicaciones, estable, critico.',
            'estado.in' => 'El estado actual debe ser uno de: recuperacion, alta, seguimiento, hospitalizado.',
            'fecha_control.date' => 'La fecha de control debe ser una fecha válida.',
            'descripcion.max' => 'La descripción no puede exceder los 1000 caracteres.',
            'recomendaciones.max' => 'Las recomendaciones no pueden exceder los 500 caracteres.',
            'farmacos_asociados.*.farmaco_id.required_with' => 'El ID del fármaco es requerido.',
            'farmacos_asociados.*.farmaco_id.exists' => 'El fármaco seleccionado no existe.',
            'farmacos_asociados.*.dosis.required_with' => 'La dosis es requerida.',
            'farmacos_asociados.*.dosis.numeric' => 'La dosis debe ser un número.',
            'farmacos_asociados.*.dosis.min' => 'La dosis debe ser mayor a 0.',
            'farmacos_asociados.*.etapa_aplicacion.required_with' => 'La etapa de aplicación es requerida.',
            'farmacos_asociados.*.etapa_aplicacion.in' => 'La etapa de aplicación no es válida.',
            'archivos.*.max' => 'Los archivos no pueden superar los 10MB.',
            'diagnosticos.*.exists' => 'Uno o más diagnósticos seleccionados no existen.',
        ];
    }

    /**
     * Validación adicional con lógica de negocio
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            
            // 🔍 DEBUG: Ver todos los parámetros de la ruta
            $routeParams = $this->route()->parameters();
            
            // 🔍 DEBUG: Intentar obtener los IDs de diferentes formas
            $mascotaId = $this->route('mascotaId') ?? $this->route('mascota') ?? $this->input('mascota_id');
            $cirugiaId = $this->route('cirugiaId') ?? $this->route('cirugia') ?? $this->route('id');
            
            // Log detallado para debug
            Log::info('🔍 DEBUG - Parámetros de ruta en UpdateCirugiaRequest', [
                'route_params_completos' => $routeParams,
                'mascotaId_encontrado' => $mascotaId,
                'cirugiaId_encontrado' => $cirugiaId,
                'url_actual' => $this->url(),
                'method' => $this->method(),
                'input_data' => $this->except(['archivos'])
            ]);

            if (!$cirugiaId || !$mascotaId) {
                Log::error('❌ No se pudieron obtener los IDs de la ruta', [
                    'route_params' => $routeParams,
                    'mascotaId' => $mascotaId,
                    'cirugiaId' => $cirugiaId
                ]);
                
                $validator->errors()->add(
                    'cirugia', 
                    'Error interno: No se pudieron identificar la mascota o la cirugía.'
                );
                return;
            }

            // Buscar la cirugía asegurando que pertenezca a la mascota
            $this->cirugia = Cirugia::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->find($cirugiaId);

            if (!$this->cirugia) {
                Log::error('❌ Cirugía no encontrada', [
                    'mascotaId_buscado' => $mascotaId,
                    'cirugiaId_buscado' => $cirugiaId,
                    'existe_mascota' => \App\Models\Mascota::find($mascotaId) ? 'si' : 'no',
                    'existe_cirugia_sin_filtro' => \App\Models\ProcedimientosMedicos\Cirugia::find($cirugiaId) ? 'si' : 'no'
                ]);
                
                $validator->errors()->add(
                    'cirugia', 
                    'La cirugía no existe o no pertenece a esta mascota.'
                );
                return;
            }

            Log::info('✅ Cirugía encontrada para validación', [
                'cirugia_id' => $this->cirugia->id,
                'mascota_id' => $mascotaId,
                'tipo_cirugia_actual' => $this->cirugia->tipo_cirugia_id
            ]);

            // 1. VALIDACIÓN CRUZADA: Solo si se está cambiando el tipo de cirugía
            $nuevoTipoCirugiaId = $this->input('tipo_cirugia_id');
            $tipoCambiado = $nuevoTipoCirugiaId && $nuevoTipoCirugiaId != $this->cirugia->tipo_cirugia_id;

            if ($tipoCambiado) {
                try {
                    $validacion = $this->validationService->validarAntesDeRegistro(
                        (int) $mascotaId,
                        (int) $nuevoTipoCirugiaId
                    );

                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_cirugia_id', $error);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error en validación cruzada', [
                        'error' => $e->getMessage()
                    ]);
                    
                    $validator->errors()->add(
                        'tipo_cirugia_id',
                        'Error en validación: ' . $e->getMessage()
                    );
                }
            }

            // 2. Validar fecha de cirugía si se está actualizando
            if ($this->has('fecha') && !$validator->errors()->has('fecha')) {
                $tipoIdParaValidacion = $nuevoTipoCirugiaId ?? $this->cirugia->tipo_cirugia_id;
                
                $fechaValidation = $this->validationService->validarCirugiaCompleta([
                    'mascota_id' => (int) $mascotaId,
                    'tipo_cirugia_id' => (int) $tipoIdParaValidacion,
                    'fecha_cirugia' => $this->input('fecha'),
                ]);

                if (!$fechaValidation['valido']) {
                    foreach ($fechaValidation['errors'] as $error) {
                        $validator->errors()->add('fecha', $error);
                    }
                }
            }

            // 3. Validar fecha de control
            if ($this->has('fecha_control') && $this->input('fecha_control') && !$validator->errors()->has('fecha_control')) {
                $fechaCirugia = $this->input('fecha') ?? $this->cirugia->fecha_cirugia;
                
                if ($fechaCirugia) {
                    try {
                        $fechaCirugiaCarbon = Carbon::parse($fechaCirugia);
                        $fechaControlCarbon = Carbon::parse($this->input('fecha_control'));
                        
                        if ($fechaControlCarbon->lte($fechaCirugiaCarbon)) {
                            $validator->errors()->add(
                                'fecha_control',
                                'La fecha de control debe ser posterior a la fecha de la cirugía.'
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error('Error parseando fechas', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // 4. Registrar resumen
            if (!$validator->errors()->any()) {
                Log::info('✅ Validación completada sin errores', [
                    'cirugia_id' => $cirugiaId,
                    'tipo_cirugia_cambiado' => $tipoCambiado
                ]);
            }
        });
    }

    protected function failedValidation($validator)
    {
        Log::warning('❌ Errores de validación', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['archivos'])
        ]);

        throw new ValidationException(
            $validator,
            response()->json([
                'success' => false,
                'message' => 'Error de validación en la actualización de la cirugía',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }

    public function getCirugia(): ?Cirugia
    {
        return $this->cirugia;
    }
}