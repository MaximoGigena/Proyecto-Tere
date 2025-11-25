<?php
// app/Http/Controllers/ControllersProcedimientos/DesparasitacionController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Desparasitacion;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoDesparasitacion;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;

class DesparasitacionController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nueva desparasitación
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposDesparasitacion = TipoDesparasitacion::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('desparasitaciones.create', compact('mascota', 'tiposDesparasitacion', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nueva desparasitación
     */
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del caso de uso
        $validated = $request->validate([
            'tipo_desparasitacion_id' => 'required|exists:tipos_desparasitacion,id',
            'fecha' => 'required|date',
            'nombre_producto' => 'required|string|max:255',
            'dosis' => 'required|string|max:100',
            'frecuencia_valor' => 'required|integer|min:1',
            'frecuencia_unidad' => 'required|in:dias,semanas,meses',
            'peso' => 'nullable|numeric|min:0',
            'proxima_fecha' => 'nullable|date|after:fecha',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
        ]);

        try {
            $desparasitacionCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$desparasitacionCreada, &$mascotaData) {
                // 1. Crear el registro específico de Desparasitacion
                $desparasitacion = Desparasitacion::create([
                    'tipo_desparasitacion_id' => $validated['tipo_desparasitacion_id'],
                    'fecha' => $validated['fecha'],
                    'nombre_producto' => $validated['nombre_producto'],
                    'dosis' => $validated['dosis'],
                    'frecuencia_valor' => $validated['frecuencia_valor'],
                    'frecuencia_unidad' => $validated['frecuencia_unidad'],
                    'peso' => $validated['peso'] ?? null,
                    'proxima_fecha' => $validated['proxima_fecha'] ?? null,
                    'observaciones' => $validated['observaciones'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'preventivo',
                    'fecha_aplicacion' => $validated['fecha'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Asociar la desparasitación con el proceso médico
                $desparasitacion->procesoMedico()->save($procesoMedico);
                
                // 4. Cargar relaciones para el PDF
                $desparasitacionCreada = $desparasitacion->load([
                    'tipoDesparasitacion',
                    'procesoMedico.centroVeterinario'
                ]);
                
                // 5. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 6. Enviar certificado PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($desparasitacionCreada && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoDesparasitacion(
                        $desparasitacionCreada, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y certificado enviado';
                    
                    Log::info('✅ Certificado de desparasitación enviado exitosamente', [
                        'desparasitacion_id' => $desparasitacionCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando certificado de desparasitación', [
                        'desparasitacion_id' => $desparasitacionCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Desparasitación registrada exitosamente' . $mensajeEnvio,
                'data' => [
                    'desparasitacion' => $desparasitacionCreada,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar desparasitación', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la desparasitación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de desparasitaciones de una mascota
     */
    public function index($mascotaId): JsonResponse
    {
        try {
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            $desparasitaciones = Desparasitacion::with([
                'tipoDesparasitacion',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function($desparasitacion) {
                return [
                    'id' => $desparasitacion->id,
                    'tipo' => $desparasitacion->tipoDesparasitacion->nombre ?? 'Tipo no especificado',
                    'tipo_desparasitacion_id' => $desparasitacion->tipo_desparasitacion_id,
                    'nombre_producto' => $desparasitacion->nombre_producto,
                    'dosis' => $desparasitacion->dosis,
                    'fecha' => $desparasitacion->fecha,
                    'frecuencia' => $desparasitacion->frecuencia_valor . ' ' . $desparasitacion->frecuencia_unidad,
                    'peso' => $desparasitacion->peso,
                    'proxima_fecha' => $desparasitacion->proxima_fecha,
                    'centro_veterinario' => $desparasitacion->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $desparasitacion->procesoMedico->veterinario->name ?? 'No especificado',
                    'observaciones' => $desparasitacion->procesoMedico->observaciones,
                    'created_at' => $desparasitacion->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $desparasitaciones
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener desparasitaciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las desparasitaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una desparasitación
     */
    public function show($id): JsonResponse
    {
        try {
            $desparasitacion = Desparasitacion::with([
                'tipoDesparasitacion',
                'procesoMedico.mascota',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])->find($id);

            if (!$desparasitacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desparasitación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $desparasitacion
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener desparasitación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener la desparasitación'
            ], 500);
        }
    }

    /**
     * Obtener tipos de desparasitación
     */
    public function obtenerTiposDesparasitacion(): JsonResponse
    {
        try {
            $tipos = TipoDesparasitacion::all();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener tipos de desparasitación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los tipos de desparasitación'
            ], 500);
        }
    }
}