<?php
// app/Http/Controllers/ControllersProcedimientos/VacunaController.php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Vacuna;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoVacuna;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;

class VacunaController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Mostrar formulario para crear nueva vacuna
     */
    public function create($mascotaId)
    {
        $mascota = Mascota::findOrFail($mascotaId);
        $tiposVacuna = TipoVacuna::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('vacunas.create', compact('mascota', 'tiposVacuna', 'centrosVeterinarios'));
    }

    /**
     * Almacenar nueva vacuna
     */
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del caso de uso
        $validated = $request->validate([
            'tipo_vacuna_id' => 'required|exists:tipos_vacuna,id',
            'fecha_aplicacion' => 'required|date',
            'numero_dosis' => 'required|string|max:50',
            'lote_serie' => 'required|string|max:100',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'fecha_proxima_dosis' => 'nullable|date|after:fecha_aplicacion',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
            'medio_envio' => 'required|in:email,telegram,whatsapp', // Nueva validación
        ]);

        try {
            $vacunaCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$vacunaCreada, &$mascotaData) {
                // 1. Crear el registro específico de Vacuna
                $vacuna = Vacuna::create([
                    'tipo_vacuna_id' => $validated['tipo_vacuna_id'],
                    'numero_dosis' => $validated['numero_dosis'],
                    'lote_serie' => $validated['lote_serie'],
                    'fecha_proxima_dosis' => $validated['fecha_proxima_dosis'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'preventivo',
                    'fecha_aplicacion' => $validated['fecha_aplicacion'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);

                // 3. Asociar la vacuna con el proceso médico
                $vacuna->procesoMedico()->save($procesoMedico);
                
                // 4. Cargar relaciones para el PDF
                $vacunaCreada = $vacuna->load([
                    'tipo',
                    'procesoMedico.centroVeterinario'
                ]);
                
                // 5. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 6. Enviar certificado PDF después del registro exitoso
            $mensajeEnvio = '';
            if ($vacunaCreada && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarCertificadoVacuna(
                        $vacunaCreada, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y certificado enviado';
                    
                    Log::info('✅ Certificado de vacuna enviado exitosamente', [
                        'vacuna_id' => $vacunaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando certificado: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando certificado de vacuna', [
                        'vacuna_id' => $vacunaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // DEVUELVE JSON PARA API
            return response()->json([
                'success' => true,
                'message' => 'Vacuna registrada exitosamente' . $mensajeEnvio,
                'data' => [
                    'vacuna' => $vacunaCreada,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar vacuna', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // DEVUELVE JSON ERROR PARA API
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la vacuna: ' . $e->getMessage()
            ], 500);
        }
    }

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

            // Obtener las vacunas con sus relaciones
            $vacunas = Vacuna::with([
                'tipo',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($vacuna) {
                return [
                    'id' => $vacuna->id,
                    'nombre' => $vacuna->tipo->nombre ?? 'Vacuna no especificada',
                    'tipo_vacuna_id' => $vacuna->tipo_vacuna_id,
                    'numero_dosis' => $vacuna->numero_dosis,
                    'lote_serie' => $vacuna->lote_serie,
                    'fecha_aplicacion' => $vacuna->procesoMedico->fecha_aplicacion ?? null,
                    'fecha_proxima_dosis' => $vacuna->fecha_proxima_dosis,
                    'centro_veterinario' => $vacuna->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $vacuna->procesoMedico->veterinario->name ?? 'No especificado',
                    'observaciones' => $vacuna->procesoMedico->observaciones,
                    'created_at' => $vacuna->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $vacunas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener vacunas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las vacunas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una vacuna
     */
    public function show($id): JsonResponse
    {
        try {
            Log::info('🔍 Buscando mascota con ID:', ['id' => $id]);
            
            $mascota = Mascota::with(['usuario', 'especie'])->find($id);

            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Si necesitas verificar permisos, hazlo de otra manera
            // Por ejemplo, verificar si el usuario autenticado es el dueño o veterinario
            $user = auth()->user();
            
            // Verificación básica sin usar roles
            if ($mascota->usuario_id !== $user->id) {
                // Opcional: verificar si es veterinario de otras maneras
                // Por ejemplo, si tienes un campo 'es_veterinario' en users
                if (!isset($user->es_veterinario) || !$user->es_veterinario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autorizado para ver esta mascota'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $mascota
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener mascota:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener la mascota'
            ], 500);
        }
    }



    /**
     * Mostrar formulario para editar vacuna
     */
    public function edit(Vacuna $vacuna)
    {
        $vacuna->load('procesoMedico');
        $tiposVacuna = TipoVacuna::all();
        $centrosVeterinarios = CentroVeterinario::where('activo', true)->get();

        return view('vacunas.edit', compact('vacuna', 'tiposVacuna', 'centrosVeterinarios'));
    }

    /**
     * Actualizar vacuna
     */
    public function update(Request $request, Vacuna $vacuna)
    {
        $validated = $request->validate([
            'tipo_vacuna_id' => 'required|exists:tipos_vacuna,id',
            'fecha_aplicacion' => 'required|date',
            'numero_dosis' => 'required|string|max:50',
            'lote_serie' => 'required|string|max:100',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinario,id',
            'fecha_proxima_dosis' => 'nullable|date|after:fecha_aplicacion',
            'observaciones' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $vacuna) {
                // 1. Actualizar la vacuna
                $vacuna->update([
                    'tipo_vacuna_id' => $validated['tipo_vacuna_id'],
                    'numero_dosis' => $validated['numero_dosis'],
                    'lote_serie' => $validated['lote_serie'],
                    'fecha_proxima_dosis' => $validated['fecha_proxima_dosis'] ?? null,
                ]);

                // 2. Actualizar el proceso médico asociado
                $vacuna->procesoMedico->update([
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'fecha_aplicacion' => $validated['fecha_aplicacion'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'costo' => $validated['costo'] ?? null,
                ]);
            });

            return redirect()
                ->route('mascotas.historial', $vacuna->procesoMedico->mascota_id)
                ->with('success', 'Vacuna actualizada exitosamente.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la vacuna: ' . $e->getMessage());
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
}