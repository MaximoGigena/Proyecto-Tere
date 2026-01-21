<?php
// app/Http/Controllers/ControllersProcedimientos/FarmacoController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Farmaco;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoFarmaco;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;
use Illuminate\Support\Facades\Storage;

class FarmacoController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nuevo fármaco
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposFarmaco = TipoFarmaco::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('farmacos.create', compact('mascota', 'tiposFarmaco', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nuevo fármaco
     */
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos de tu vista Vue
        $validated = $request->validate([
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
            'medio_envio' => 'required|in:email,telegram,whatsapp',
            'archivos.*' => 'nullable|file|max:10240', // Máximo 10MB por archivo
        ]);

        try {
            $farmacoCreado = null;
            $mascotaData = null;
            $archivosGuardados = [];

            DB::transaction(function () use ($validated, $mascotaId, &$farmacoCreado, &$mascotaData, &$archivosGuardados, $request) {
                // 1. Crear el registro específico de Farmaco
                $farmaco = Farmaco::create([
                    'tipo_farmaco_id' => $validated['tipo_farmaco_id'],
                    'fecha_administracion' => $validated['fecha_administracion'],
                    'frecuencia' => $validated['frecuencia'],
                    'duracion_tratamiento' => $validated['duracion'],
                    'dosis' => $validated['dosis'],
                    'unidad_dosis' => $validated['unidad'],
                    'proxima_dosis' => $validated['proxima_dosis'] ?? null,
                    'reacciones_adversas' => $validated['reacciones'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'clinico',
                    'fecha_aplicacion' => $validated['fecha_administracion'],
                    'observaciones' => 'Administración de fármaco: ' . ($validated['recomendaciones'] ?? 'Sin observaciones'),
                    'costo' => null, // Puedes agregar campo costo si lo necesitas
                ]);

                // 3. Asociar el fármaco con el proceso médico
                $farmaco->procesoMedico()->save($procesoMedico);

                // 4. Guardar archivos adjuntos
                if ($request->hasFile('archivos')) {
                    foreach ($request->file('archivos') as $key => $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            $nombreArchivo = time() . '_' . uniqid() . '_' . $archivo->getClientOriginalName();
                            $path = $archivo->storeAs('farmacos/' . $farmaco->id, $nombreArchivo, 'public');
                            
                            $archivosGuardados[] = [
                                'nombre' => $archivo->getClientOriginalName(),
                                'ruta' => $path,
                                'tipo' => $archivo->getMimeType(),
                                'tamanio' => $archivo->getSize()
                            ];
                        }
                    }
                }

                // 5. Cargar relaciones para posible uso en PDF
                $farmacoCreado = $farmaco->load([
                    'tipoFarmaco',
                    'procesoMedico.centroVeterinario'
                ]);
                
                // 6. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 7. Enviar receta PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($farmacoCreado && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarRecetaFarmaco(
                        $farmacoCreado, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y receta enviada';
                    
                    Log::info('✅ Receta de fármaco enviada exitosamente', [
                        'farmaco_id' => $farmacoCreado->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando receta: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando receta de fármaco', [
                        'farmaco_id' => $farmacoCreado->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // DEVUELVE JSON PARA API
            return response()->json([
                'success' => true,
                'message' => 'Fármaco registrado exitosamente' . $mensajeEnvio,
                'data' => [
                    'farmaco' => $farmacoCreado,
                    'archivos' => $archivosGuardados,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar fármaco', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // DEVUELVE JSON ERROR PARA API
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los fármacos de una mascota
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

            // Obtener los fármacos con sus relaciones (solo activos)
            $farmacos = Farmaco::with([
                'tipoFarmaco',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId)
                    ->whereNull('deleted_at'); // Solo procesos médicos activos
            })
            ->activos() // Usamos el scope activos definido en el modelo
            ->orderBy('fecha_administracion', 'desc')
            ->get()
            ->map(function($farmaco) {
                return [
                    'id' => $farmaco->id,
                    'nombre' => $farmaco->tipoFarmaco->nombre ?? 'Fármaco no especificado',
                    'tipo_farmaco_id' => $farmaco->tipo_farmaco_id,
                    'fecha_administracion' => $farmaco->fecha_administracion,
                    'frecuencia' => $farmaco->frecuencia,
                    'duracion_tratamiento' => $farmaco->duracion_tratamiento,
                    'dosis' => $farmaco->dosis,
                    'unidad_dosis' => $farmaco->unidad_dosis,
                    'proxima_dosis' => $farmaco->proxima_dosis,
                    'reacciones_adversas' => $farmaco->reacciones_adversas,
                    'recomendaciones_tutor' => $farmaco->recomendaciones_tutor,
                    'centro_veterinario' => $farmaco->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $farmaco->procesoMedico->veterinario->name ?? 'No especificado',
                    'observaciones' => $farmaco->procesoMedico->observaciones,
                    'created_at' => $farmaco->created_at,
                    'deleted_at' => $farmaco->deleted_at, // Incluir esta información
                    'esta_activo' => is_null($farmaco->deleted_at) // Campo adicional para frontend
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $farmacos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener fármacos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los fármacos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un fármaco específico
     */
    public function show($mascotaId, $farmacoId): JsonResponse
    {
        try {
            $farmaco = Farmaco::with([
                'tipoFarmaco',
                'procesoMedico.mascota',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])->find($farmacoId);

            if (!$farmaco) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fármaco no encontrado'
                ], 404);
            }

            // Asegurar que los nombres de los campos coincidan con Vue
            $data = [
                'id' => $farmaco->id,
                'tipo_farmaco_id' => $farmaco->tipo_farmaco_id,
                'fecha_administracion' => $farmaco->fecha_administracion,
                'frecuencia' => $farmaco->frecuencia,
                'duracion' => $farmaco->duracion_tratamiento, // Mapear a 'duracion'
                'dosis' => $farmaco->dosis,
                'unidad' => $farmaco->unidad_dosis, // Mapear a 'unidad'
                'centro_veterinario_id' => $farmaco->procesoMedico->centro_veterinario_id ?? null,
                'proxima_dosis' => $farmaco->proxima_dosis,
                'reacciones' => $farmaco->reacciones_adversas,
                'recomendaciones' => $farmaco->recomendaciones_tutor,
                'medio_envio' => $farmaco->procesoMedico->medio_envio ?? null,
                'tipo_farmaco' => $farmaco->tipoFarmaco,
                'proceso_medico' => $farmaco->procesoMedico,
                // Agregar archivos si es necesario
                'archivos' => $this->obtenerArchivosArray($farmaco->id)
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener fármaco:', [
                'id' => $farmacoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener el fármaco'
            ], 500);
        }
    }
    /**
     * Actualizar fármaco
     */
    public function update(Request $request, $mascotaId, $farmacoId)
    {
        $validated = $request->validate([
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
        ]);

        try {
            // Buscar el fármaco
            $farmaco = Farmaco::findOrFail($farmacoId);
            
            DB::transaction(function () use ($validated, $farmaco) {
                // 1. Actualizar el fármaco (CORREGIR NOMBRES DE CAMPOS)
                $farmaco->update([
                    'tipo_farmaco_id' => $validated['tipo_farmaco_id'],
                    'fecha_administracion' => $validated['fecha_administracion'],
                    'frecuencia' => $validated['frecuencia'],
                    'duracion_tratamiento' => $validated['duracion'], // CAMBIADO
                    'dosis' => $validated['dosis'],
                    'unidad_dosis' => $validated['unidad'], // CAMBIADO
                    'proxima_dosis' => $validated['proxima_dosis'] ?? null,
                    'reacciones_adversas' => $validated['reacciones'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones'] ?? null,
                ]);

                // 2. Actualizar el proceso médico asociado
                if ($farmaco->procesoMedico) {
                    $farmaco->procesoMedico->update([
                        'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                        'fecha_aplicacion' => $validated['fecha_administracion'],
                        'observaciones' => 'Administración de fármaco: ' . ($validated['recomendaciones'] ?? 'Sin observaciones'),
                    ]);
                }
            });

            // Cargar relaciones para la respuesta
            $farmaco->load([
                'tipoFarmaco',
                'procesoMedico.centroVeterinario',
                'procesoMedico.mascota'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fármaco actualizado exitosamente',
                'data' => [
                    'farmaco' => $farmaco,
                    'procesoMedico' => $farmaco->procesoMedico,
                    'centro_veterinario_id' => $farmaco->procesoMedico->centro_veterinario_id ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar fármaco: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar fármaco
     */
    public function destroy($mascotaId, $farmacoId): JsonResponse
    {
        try {
            DB::transaction(function () use ($mascotaId, $farmacoId) { // <- Añadir $mascotaId aquí
                // Buscar el fármaco
                $farmaco = Farmaco::findOrFail($farmacoId);
                
                // 1. Realizar baja lógica del fármaco
                $farmaco->delete(); // Esto actualizará el campo deleted_at
                
                // 2. Realizar baja lógica del proceso médico asociado
                if ($farmaco->procesoMedico) {
                    $farmaco->procesoMedico->delete(); // También baja lógica en ProcesoMedico
                }

                Log::info('✅ Fármaco marcado como eliminado (baja lógica)', [
                    'farmaco_id' => $farmacoId,
                    'mascota_id' => $mascotaId, // Ahora $mascotaId está disponible
                    'fecha_eliminacion' => now()
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Fármaco eliminado exitosamente (baja lógica)'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar fármaco: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el fármaco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para obtener medios de contacto de un usuario
     */
    public function obtenerMediosContacto($usuarioId): JsonResponse
    {
        try {
            $contacto = ContactoUsuario::where('usuario_id', $usuarioId)->first();

            if (!$contacto) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de contacto para este usuario',
                    'data' => []
                ], 404);
            }

            $medios = [];

            // Email
            if ($contacto->email) {
                $medios[] = [
                    'id' => 'email',
                    'nombre' => 'Email',
                    'valor' => $contacto->email,
                    'icon' => 'Mail',
                    'color' => 'text-blue-500'
                ];
            }

            // WhatsApp
            if ($contacto->telefono) {
                $medios[] = [
                    'id' => 'whatsapp',
                    'nombre' => 'WhatsApp',
                    'valor' => $contacto->telefono,
                    'icon' => 'MessageCircle',
                    'color' => 'text-green-500'
                ];
            }

            // Telegram
            if ($contacto->telegram_chat_id) {
                $medios[] = [
                    'id' => 'telegram',
                    'nombre' => 'Telegram',
                    'valor' => 'Chat ID: ' . $contacto->telegram_chat_id,
                    'icon' => 'Send',
                    'color' => 'text-sky-500'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $medios
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo medios de contacto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar medios de contacto: ' . $e->getMessage()
            ], 500);
        }
    }

    // Método auxiliar para obtener archivos como array
    private function obtenerArchivosArray($farmacoId)
    {
        $directorio = 'farmacos/' . $farmacoId;
        
        if (!Storage::disk('public')->exists($directorio)) {
            return [];
        }

        $archivos = [];
        $files = Storage::disk('public')->files($directorio);
        
        foreach ($files as $file) {
            $archivos[] = [
                'nombre' => basename($file),
                'url' => Storage::url($file),
                'tipo' => Storage::mimeType($file)
            ];
        }
        
        return $archivos;
    }

    /**
     * Obtener archivos adjuntos de un fármaco
     */
    public function obtenerArchivos($farmacoId): JsonResponse
    {
        try {
            $farmaco = Farmaco::find($farmacoId);
            
            if (!$farmaco) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fármaco no encontrado'
                ], 404);
            }

            $directorio = 'farmacos/' . $farmacoId;
            
            if (!Storage::disk('public')->exists($directorio)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $archivos = [];
            $files = Storage::disk('public')->files($directorio);
            
            foreach ($files as $file) {
                $archivos[] = [
                    'nombre' => basename($file),
                    'ruta' => Storage::url($file),
                    'tamanio' => Storage::disk('public')->size($file),
                    'fecha' => Storage::disk('public')->lastModified($file)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $archivos
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo archivos de fármaco: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar archivos adjuntos'
            ], 500);
        }
    }
}