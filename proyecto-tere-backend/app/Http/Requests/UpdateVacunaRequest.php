<?php
// app/Http/Requests/UpdateVacunaRequest.php

namespace App\Http\Requests;

use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\TiposProcedimientos\TipoVacuna;
use App\Services\Validaciones\VacunaValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVacunaRequest extends FormRequest
{
    protected $validationService;
    protected $vacuna;

    public function __construct(VacunaValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Obtener la vacuna de la ruta
        $vacunaId = $this->route('id');
        $this->vacuna = Vacuna::with('procesoMedico.mascota')->find($vacunaId);
        
        if (!$this->vacuna) {
            return false;
        }

        // Verificar permisos
        $user = auth()->user();
        $veterinarioId = $this->vacuna->procesoMedico->veterinario_id ?? null;
        
        return $veterinarioId === $user->id || $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tipo_vacuna_id' => 'sometimes|required|exists:tipos_vacuna,id',
            'fecha_aplicacion' => 'sometimes|required|date',
            'numero_dosis' => 'sometimes|required|string|max:50',
            'lote_serie' => 'sometimes|required|string|max:100',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'fecha_proxima_dosis' => 'nullable|date|after:fecha_aplicacion',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'medio_envio' => 'sometimes|in:email,telegram,whatsapp',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si se está cambiando el tipo de vacuna
            if ($this->has('tipo_vacuna_id') && 
                $this->tipo_vacuna_id != $this->vacuna->tipo_vacuna_id) {
                
                $mascota = $this->vacuna->procesoMedico->mascota;
                $nuevoTipoVacuna = TipoVacuna::find($this->tipo_vacuna_id);
                
                if ($nuevoTipoVacuna) {
                    $validacion = $this->validationService->validarMascotaParaTipoVacuna(
                        $mascota, 
                        $nuevoTipoVacuna
                    );
                    
                    if (!$validacion['valido']) {
                        foreach ($validacion['errors'] as $error) {
                            $validator->errors()->add('tipo_vacuna_id', $error);
                        }
                    }
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $validator->errors()
        ], 422));
    }
}