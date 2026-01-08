<?php
// app/Http/Controllers/ControllersProcedimientos/DiagnosticoController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Diagnostico;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoDiagnostico;
use App\Models\CentroVeterinario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\EnvioDocumentosService;

class DiagnosticoController extends Controller
{

    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nuevo diagnóstico
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposDiagnostico = TipoDiagnostico::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('diagnosticos.create', compact('mascota', 'tiposDiagnostico', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nuevo diagnóstico
     */
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del formulario
        $validated = $request->validate([
            'tipo_diagnostico_id' => 'required|exists:tipos_diagnostico,id',
            'nombre' => 'required|string|max:255',
            'fecha_diagnostico' => 'required|date',
            'estado' => 'required|in:activo,resuelto,cronico,seguimiento,sospecha',
            'diagnosticos_diferenciales' => 'nullable|string',
            'examenes_complementarios' => 'nullable|string',
            'conducta_terapeutica' => 'nullable|string',
            'observaciones' => 'nullable|string|max:500',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'costo' => 'nullable|numeric|min:0',
            'archivos.*' => 'nullable|file|max:10240', // 10MB máximo
            'medio_envio' => 'required|in:email,telegram,whatsapp',
        ]);

       try {
        $diagnosticoCreado = null;
        $mascotaData = null; // Agregar esta variable

        DB::transaction(function () use ($validated, $mascotaId, &$diagnosticoCreado, &$mascotaData, $request) {
            // 1. Crear el registro específico de Diagnostico
            $diagnostico = Diagnostico::create([
                'tipo_diagnostico_id' => $validated['tipo_diagnostico_id'],
                'nombre' => $validated['nombre'],
                'fecha_diagnostico' => $validated['fecha_diagnostico'],
                'estado' => $validated['estado'],
                'diagnosticos_diferenciales' => $validated['diagnosticos_diferenciales'] ?? null,
                'examenes_complementarios' => $validated['examenes_complementarios'] ?? null,
                'conducta_terapeutica' => $validated['conducta_terapeutica'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // 2. Crear el registro general en ProcesoMedico
            $procesoMedico = new ProcesoMedico([
                'mascota_id' => $mascotaId,
                'veterinario_id' => Auth::id(),
                'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                'categoria' => 'clinico',
                'fecha_aplicacion' => $validated['fecha_diagnostico'],
                'observaciones' => $validated['observaciones'] ?? null,
                'costo' => $validated['costo'] ?? null,
            ]);

            // 3. Asociar el diagnóstico con el proceso médico
            $diagnostico->procesoMedico()->save($procesoMedico);

            // 4. Manejar archivos adjuntos
            if ($request->hasFile('archivos')) {
                $this->guardarArchivos($diagnostico, $request->file('archivos'));
            }

            // 5. Cargar relaciones para la respuesta
            $diagnosticoCreado = $diagnostico->load([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ]);
            
            // 6. Obtener datos de la mascota con relaciones
            $mascotaData = Mascota::with('usuario')->find($mascotaId); // Agregar esta línea
        });

        // 7. Enviar certificado PDF después del registro exitoso
        $mensajeEnvio = '';
        if ($diagnosticoCreado && $mascotaData) {
            try {
                $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoDiagnostico(
                    $diagnosticoCreado, 
                    $mascotaData, 
                    $validated['medio_envio']
                );

                $mensajeEnvio = ' y certificado enviado';
                
                Log::info('✅ Certificado de diagnóstico enviado exitosamente', [
                    'diagnostico_id' => $diagnosticoCreado->id,
                    'mascota_id' => $mascotaId,
                    'medio_envio' => $validated['medio_envio'],
                    'usuario_id' => $mascotaData->usuario_id
                ]);

            } catch (\Exception $e) {
                $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';
                
                Log::error('❌ Error enviando certificado de diagnóstico', [
                    'diagnostico_id' => $diagnosticoCreado->id,
                    'mascota_id' => $mascotaId,
                    'medio_envio' => $validated['medio_envio'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('✅ Diagnóstico registrado exitosamente', [
            'diagnostico_id' => $diagnosticoCreado->id,
            'mascota_id' => $mascotaId,
            'usuario_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Diagnóstico registrado exitosamente' . $mensajeEnvio,
            'data' => [
                'diagnostico' => $diagnosticoCreado,
                'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
            ]
        ], 201);

    } catch (\Exception $e) {
        Log::error('❌ Error completo al registrar diagnóstico', [
            'mascota_id' => $mascotaId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al registrar el diagnóstico: ' . $e->getMessage()
        ], 500);
    }
    }

    /**
     * Listar todos los diagnósticos de una mascota
     */
    public function index($mascotaId): JsonResponse
    {
        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener los diagnósticos con sus relaciones
            $diagnosticos = Diagnostico::with([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha_diagnostico', 'desc')
            ->get()
            ->map(function($diagnostico) {
                return [
                    'id' => $diagnostico->id,
                    'nombre' => $diagnostico->nombre,
                    'tipo_diagnostico' => $diagnostico->tipoDiagnostico->nombre ?? 'Tipo no especificado',
                    'fecha_diagnostico' => $diagnostico->fecha_diagnostico,
                    'fecha_diagnostico_formateada' => $diagnostico->fecha_diagnostico->format('d/m/Y'),
                    'estado' => $diagnostico->estado,
                    'estado_display' => $this->getEstadoDisplay($diagnostico->estado),
                    'centro_veterinario' => $diagnostico->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $diagnostico->procesoMedico->veterinario->name ?? 'No especificado',
                    'diagnosticos_diferenciales' => $diagnostico->diagnosticos_diferenciales,
                    'examenes_complementarios' => $diagnostico->examenes_complementarios,
                    'conducta_terapeutica' => $diagnostico->conducta_terapeutica,
                    'observaciones' => $diagnostico->observaciones,
                    'esta_activo' => $diagnostico->estaActivo(),
                    'created_at' => $diagnostico->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $diagnosticos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener diagnósticos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los diagnósticos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un diagnóstico específico
     */
    public function show($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::with([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario',
                'procesoMedico.mascota'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->findOrFail($diagnosticoId);

            // Cargar archivos adjuntos si existen
            $archivos = [];
            $archivosPath = "diagnosticos/{$diagnostico->id}";
            if (Storage::exists($archivosPath)) {
                $archivos = Storage::files($archivosPath);
            }

            $diagnosticoArray = $diagnostico->toArray();
            $diagnosticoArray['fecha_diagnostico_formateada'] = $diagnostico->fecha_diagnostico->format('d/m/Y');
            $diagnosticoArray['estado_display'] = $this->getEstadoDisplay($diagnostico->estado);
            $diagnosticoArray['esta_activo'] = $diagnostico->estaActivo();
            $diagnosticoArray['archivos'] = $archivos;
            $diagnosticoArray['diagnosticos_diferenciales_array'] = $diagnostico->diagnosticos_diferenciales_array;
            $diagnosticoArray['examenes_complementarios_array'] = $diagnostico->examenes_complementarios_array;

            return response()->json([
                'success' => true,
                'data' => $diagnosticoArray
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener diagnóstico:', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el diagnóstico: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar diagnóstico
     */
    public function update(Request $request, $mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($diagnosticoId);

            $validated = $request->validate([
                'tipo_diagnostico_id' => 'sometimes|exists:tipos_diagnostico,id',
                'nombre' => 'sometimes|string|max:255',
                'fecha_diagnostico' => 'sometimes|date',
                'estado' => 'sometimes|in:activo,resuelto,cronico,seguimiento,sospecha',
                'diagnosticos_diferenciales' => 'nullable|string',
                'examenes_complementarios' => 'nullable|string',
                'conducta_terapeutica' => 'nullable|string',
                'observaciones' => 'nullable|string|max:500',
                'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
                'costo' => 'nullable|numeric|min:0',
                'archivos.*' => 'nullable|file|max:10240',
                'archivos_a_eliminar' => 'nullable|array', // Nombres de archivos a eliminar
                'archivos_a_eliminar.*' => 'string',
            ]);

            DB::transaction(function () use ($validated, $diagnostico, $request) {
                // 1. Actualizar el diagnóstico
                $diagnostico->update([
                    'tipo_diagnostico_id' => $validated['tipo_diagnostico_id'] ?? $diagnostico->tipo_diagnostico_id,
                    'nombre' => $validated['nombre'] ?? $diagnostico->nombre,
                    'fecha_diagnostico' => $validated['fecha_diagnostico'] ?? $diagnostico->fecha_diagnostico,
                    'estado' => $validated['estado'] ?? $diagnostico->estado,
                    'diagnosticos_diferenciales' => $validated['diagnosticos_diferenciales'] ?? $diagnostico->diagnosticos_diferenciales,
                    'examenes_complementarios' => $validated['examenes_complementarios'] ?? $diagnostico->examenes_complementarios,
                    'conducta_terapeutica' => $validated['conducta_terapeutica'] ?? $diagnostico->conducta_terapeutica,
                    'observaciones' => $validated['observaciones'] ?? $diagnostico->observaciones,
                ]);

                // 2. Actualizar el proceso médico asociado
                if ($diagnostico->procesoMedico) {
                    $diagnostico->procesoMedico->update([
                        'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? $diagnostico->procesoMedico->centro_veterinario_id,
                        'fecha_aplicacion' => $validated['fecha_diagnostico'] ?? $diagnostico->fecha_diagnostico,
                        'observaciones' => $validated['observaciones'] ?? $diagnostico->observaciones,
                        'costo' => $validated['costo'] ?? $diagnostico->procesoMedico->costo,
                    ]);
                }

                // 3. Manejar eliminación de archivos
                if (isset($validated['archivos_a_eliminar'])) {
                    $this->eliminarArchivos($diagnostico, $validated['archivos_a_eliminar']);
                }

                // 4. Manejar nuevos archivos
                if ($request->hasFile('archivos')) {
                    $this->guardarArchivos($diagnostico, $request->file('archivos'));
                }
            });

            // Recargar relaciones
            $diagnostico->load([
                'tipoDiagnostico',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ]);

            Log::info('✅ Diagnóstico actualizado exitosamente', [
                'diagnostico_id' => $diagnostico->id,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico actualizado exitosamente',
                'data' => $diagnostico
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar diagnóstico', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar diagnóstico
     */
    public function destroy($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($diagnosticoId);

            DB::transaction(function () use ($diagnostico) {
                // 1. Eliminar archivos adjuntos
                $archivosPath = "diagnosticos/{$diagnostico->id}";
                if (Storage::exists($archivosPath)) {
                    Storage::deleteDirectory($archivosPath);
                }

                // 2. Eliminar el proceso médico (si existe)
                if ($diagnostico->procesoMedico) {
                    $diagnostico->procesoMedico->delete();
                }

                // 3. Eliminar el diagnóstico
                $diagnostico->delete();
            });

            Log::info('✅ Diagnóstico eliminado exitosamente', [
                'diagnostico_id' => $diagnosticoId,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar diagnóstico', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar diagnóstico como resuelto
     */
    public function marcarComoResuelto($mascotaId, $diagnosticoId): JsonResponse
    {
        try {
            $diagnostico = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->findOrFail($diagnosticoId);

            $diagnostico->marcarComoResuelto();

            Log::info('✅ Diagnóstico marcado como resuelto', [
                'diagnostico_id' => $diagnosticoId,
                'mascota_id' => $mascotaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico marcado como resuelto exitosamente',
                'data' => $diagnostico
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al marcar diagnóstico como resuelto', [
                'diagnostico_id' => $diagnosticoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el diagnóstico como resuelto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de diagnósticos de una mascota
     */
    public function estadisticas($mascotaId): JsonResponse
    {
        try {
            $estadisticas = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->get()
            ->pluck('cantidad', 'estado');

            $total = $estadisticas->sum();
            $activos = Diagnostico::whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })->activos()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'activos' => $activos,
                    'por_estado' => $estadisticas,
                    'estados_display' => Diagnostico::getEstadosDropdown()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de diagnósticos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo($diagnosticoId, $nombreArchivo)
    {
        try {
            $diagnostico = Diagnostico::findOrFail($diagnosticoId);
            $path = "diagnosticos/{$diagnostico->id}/{$nombreArchivo}";

            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return Storage::download($path);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo de diagnóstico', [
                'diagnostico_id' => $diagnosticoId,
                'archivo' => $nombreArchivo,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Métodos auxiliares privados
     */
    
    private function guardarArchivos($diagnostico, $archivos)
    {
        $path = "diagnosticos/{$diagnostico->id}";
        
        foreach ($archivos as $archivo) {
            if ($archivo && $archivo->isValid()) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $extension = $archivo->getClientOriginalExtension();
                $nombreUnico = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
                
                Storage::putFileAs($path, $archivo, $nombreUnico);
                
                Log::info('✅ Archivo guardado para diagnóstico', [
                    'diagnostico_id' => $diagnostico->id,
                    'archivo' => $nombreUnico,
                    'original' => $nombreOriginal
                ]);
            }
        }
    }

    private function eliminarArchivos($diagnostico, $nombresArchivos)
    {
        foreach ($nombresArchivos as $nombreArchivo) {
            $path = "diagnosticos/{$diagnostico->id}/{$nombreArchivo}";
            if (Storage::exists($path)) {
                Storage::delete($path);
                
                Log::info('✅ Archivo eliminado de diagnóstico', [
                    'diagnostico_id' => $diagnostico->id,
                    'archivo' => $nombreArchivo
                ]);
            }
        }
    }

    private function getEstadoDisplay($estado)
    {
        $estados = [
            'activo' => 'Activo',
            'resuelto' => 'Resuelto',
            'cronico' => 'Crónico',
            'seguimiento' => 'En seguimiento',
            'sospecha' => 'Sospecha',
        ];

        return $estados[$estado] ?? $estado;
    }
}