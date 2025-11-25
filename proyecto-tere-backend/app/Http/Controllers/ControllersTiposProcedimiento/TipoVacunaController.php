<?php
// app/Http/Controllers/ControllersTiposProcedimiento/TipoVacunaController.php

namespace App\Http\Controllers\ControllersTiposProcedimiento;

use App\Http\Controllers\Controller;
use App\Models\TiposProcedimientos\TipoVacuna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TipoVacunaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Log::info('Iniciando store de TipoVacuna', ['user_id' => auth()->id()]);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipos_vacuna,nombre',
            'enfermedades' => 'required|string',
            'especies' => 'required|array',
            'especies.*' => 'required|string|in:canino,felino,equino,bovino,ave,pez,otro',
            'edad_minima' => 'required|numeric|min:0',
            'edad_unidad' => 'required|in:semanas,meses,años',
            'dosis' => 'required|numeric|min:0.1',
            'dosis_unidad' => 'required|in:ml,dosis,gotas',
            'via_administracion' => 'required|in:subcutanea,intramuscular,oral,nasal',
            'frecuencia' => 'required|in:unica,anual,semestral,personalizada',
            'frecuencia_personalizada' => 'required_if:frecuencia,personalizada|string|max:255|nullable',
            'obligatoriedad' => 'required|in:obligatoria,opcional,depende',
            'intervalo_dosis' => 'nullable|string|max:255',
            'fabricante' => 'nullable|string|max:255',
            'riesgos' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
            'lote' => 'nullable|string|max:255'
        ], [
            'nombre.required' => 'El nombre de la vacuna es obligatorio',
            'nombre.unique' => 'Ya existe un tipo de vacuna con este nombre',
            'enfermedades.required' => 'Las enfermedades que previene son obligatorias',
            'especies.required' => 'Debe seleccionar al menos una especie',
            'especies.*.in' => 'La especie seleccionada no es válida',
            'edad_minima.required' => 'La edad mínima de aplicación es obligatoria',
            'dosis.required' => 'La dosis recomendada es obligatoria',
            'via_administracion.required' => 'La vía de administración es obligatoria',
            'frecuencia.required' => 'La frecuencia de aplicación es obligatoria',
            'obligatoriedad.required' => 'La obligatoriedad es obligatoria',
            'frecuencia_personalizada.required_if' => 'Debe especificar el esquema personalizado'
        ]);

        // Validación adicional para especies
        $this->validarEspecies($validated['especies']);

        try {
            $user = auth()->user();
            $validated['veterinario_id'] = $user->userable->id;

            // 🔥 CONVERTIR EL ARRAY A JSON MANUALMENTE ANTES DE CREAR
            $validated['especies'] = json_encode($validated['especies']);

            Log::info('Datos antes de crear:', $validated);

            $tipoVacuna = TipoVacuna::create($validated);

            Log::info('TipoVacuna creado exitosamente', ['id' => $tipoVacuna->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tipo de vacuna registrado correctamente',
                'data' => $tipoVacuna
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en store de TipoVacuna', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el tipo de vacuna: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            $veterinarioId = $user->userable->id;

            $tiposVacuna = TipoVacuna::where('veterinario_id', $veterinarioId)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

            Log::info('Tipos de vacuna obtenidos exitosamente', [
                'veterinario_id' => $veterinarioId,
                'total' => $tiposVacuna->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $tiposVacuna
            ]);

        } catch (\Exception $e) {
            Log::error('Error en index de TipoVacuna', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de vacuna'
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $veterinarioId = $user->userable->id;

            Log::info('Buscando tipo de vacuna', [
                'id_solicitado' => $id,
                'veterinario_id' => $veterinarioId
            ]);

            $tipoVacuna = TipoVacuna::where('id', $id)
                ->where('veterinario_id', $veterinarioId)
                ->where('activo', true)
                ->first();

            if (!$tipoVacuna) {
                Log::warning('Tipo de vacuna no encontrado', [
                    'id' => $id,
                    'veterinario_id' => $veterinarioId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de vacuna no encontrado'
                ], 404);
            }

            Log::info('Tipo de vacuna encontrado', ['id' => $tipoVacuna->id]);

            return response()->json([
                'success' => true,
                'data' => $tipoVacuna
            ]);

        } catch (\Exception $e) {
            Log::error('Error en show de TipoVacuna', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el tipo de vacuna'
            ], 500);
        }
    }

    // También actualiza el método update
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $veterinarioId = $user->userable->id;

            Log::info('Iniciando update de TipoVacuna', [
                'id' => $id,
                'veterinario_id' => $veterinarioId
            ]);

            $tipoVacuna = TipoVacuna::where('id', $id)
                ->where('veterinario_id', $veterinarioId)
                ->where('activo', true)
                ->first();

            if (!$tipoVacuna) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de vacuna no encontrado'
                ], 404);
            }

            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:tipos_vacuna,nombre,' . $id,
                'enfermedades' => 'required|string',
                'especies' => 'required|array',
                'especies.*' => 'required|string|in:canino,felino,equino,bovino,ave,pez,otro',
                'edad_minima' => 'required|numeric|min:0',
                'edad_unidad' => 'required|in:semanas,meses,años',
                'dosis' => 'required|numeric|min:0.1',
                'dosis_unidad' => 'required|in:ml,dosis,gotas',
                'via_administracion' => 'required|in:subcutanea,intramuscular,oral,nasal',
                'frecuencia' => 'required|in:unica,anual,semestral,personalizada',
                'frecuencia_personalizada' => 'required_if:frecuencia,personalizada|string|max:255|nullable',
                'obligatoriedad' => 'required|in:obligatoria,opcional,depende',
                'intervalo_dosis' => 'nullable|string|max:255',
                'fabricante' => 'nullable|string|max:255',
                'riesgos' => 'nullable|string',
                'recomendaciones' => 'nullable|string',
                'lote' => 'nullable|string|max:255'
            ], [
                'nombre.required' => 'El nombre de la vacuna es obligatorio',
                'nombre.unique' => 'Ya existe un tipo de vacuna con este nombre',
                'enfermedades.required' => 'Las enfermedades que previene son obligatorias',
                'especies.required' => 'Debe seleccionar al menos una especie',
                'especies.*.in' => 'La especie seleccionada no es válida',
                'edad_minima.required' => 'La edad mínima de aplicación es obligatoria',
                'dosis.required' => 'La dosis recomendada es obligatoria',
                'via_administracion.required' => 'La vía de administración es obligatoria',
                'frecuencia.required' => 'La frecuencia de aplicación es obligatoria',
                'obligatoriedad.required' => 'La obligatoriedad es obligatoria',
                'frecuencia_personalizada.required_if' => 'Debe especificar el esquema personalizado'
            ]);

            $this->validarEspecies($validated['especies']);

            // 🔥 CONVERTIR EL ARRAY A JSON MANUALMENTE ANTES DE ACTUALIZAR
            $validated['especies'] = json_encode($validated['especies']);

            $tipoVacuna->update($validated);

            Log::info('TipoVacuna actualizado exitosamente', ['id' => $tipoVacuna->id]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de vacuna actualizado correctamente',
                'data' => $tipoVacuna
            ]);

        } catch (\Exception $e) {
            Log::error('Error en update de TipoVacuna', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de vacuna: ' . $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $veterinarioId = $user->userable->id;

            Log::info('Iniciando baja lógica de TipoVacuna', [
                'id' => $id,
                'veterinario_id' => $veterinarioId
            ]);

            // Buscar el tipo de vacuna que pertenece al veterinario
            $tipoVacuna = TipoVacuna::where('id', $id)
                ->where('veterinario_id', $veterinarioId)
                ->first();

            if (!$tipoVacuna) {
                Log::warning('Tipo de vacuna no encontrado para eliminar', [
                    'id' => $id,
                    'veterinario_id' => $veterinarioId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de vacuna no encontrado'
                ], 404);
            }

            // Realizar la baja lógica
            $tipoVacuna->update(['activo' => false]);

            Log::info('TipoVacuna desactivado exitosamente', [
                'id' => $tipoVacuna->id,
                'nuevo_estado' => $tipoVacuna->activo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de vacuna eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en destroy de TipoVacuna', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de vacuna: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida las especies recibidas (ahora como array)
     */
    private function validarEspecies($especies): void
        {
            $especiesPermitidas = ['canino', 'felino', 'equino', 'bovino', 'ave', 'pez', 'otro'];
            
            if (is_string($especies)) {
                $especiesArray = array_map('trim', explode(',', $especies));
            } else {
                $especiesArray = (array)$especies;
            }
            
            foreach ($especiesArray as $especie) {
                if (!in_array($especie, $especiesPermitidas)) {
                    throw new \InvalidArgumentException("La especie '$especie' no es válida. Especies permitidas: " . implode(', ', $especiesPermitidas));
                }
            }
            
            if (empty($especiesArray)) {
                throw new \InvalidArgumentException('Debe seleccionar al menos una especie');
            }
        }
}