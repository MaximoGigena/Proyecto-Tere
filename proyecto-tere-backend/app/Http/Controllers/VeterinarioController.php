<?php

namespace App\Http\Controllers;

use App\Models\Veterinario;
use App\Models\CaracteristicasVeterinario;
use App\Models\ContactoVeterinario;
use App\Models\SolicitudVeterinario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VeterinarioController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:solicitudes_veterinarios,email',
            'matricula' => 'required|string|unique:veterinarios,matricula|unique:solicitudes_veterinarios,matricula',
            'especialidad' => 'required|string|max:150',
            'experiencia' => 'nullable|integer|min:0',
            'descripcion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'emailContacto' => 'nullable|email',
            'foto0' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto4' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'foto5' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

       if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Guardar las fotos
            $fotosGuardadas = $this->guardarFotos($request);

            // Crear la solicitud en lugar del veterinario directo
            $solicitud = SolicitudVeterinario::create([
                'nombre_completo' => $request->nombre,
                'email' => $request->email,
                'matricula' => $request->matricula,
                'especialidad' => $request->especialidad,
                'anos_experiencia' => $request->experiencia ?? 0,
                'descripcion' => $request->descripcion,
                'telefono' => $request->telefono,
                'email_contacto' => $request->emailContacto,
                'fotos' => $fotosGuardadas,
                'estado' => SolicitudVeterinario::ESTADO_PENDIENTE,
                'fecha_solicitud' => now()
            ]);

            // Crear el veterinario temporal con estado pendiente
            $veterinario = Veterinario::create([
                'nombre_completo' => $request->nombre,
                'matricula' => $request->matricula,
                'especialidad' => $request->especialidad,
                'foto' => $fotosGuardadas[0] ?? null,
                'estado' => Veterinario::ESTADO_PENDIENTE,
                'activo' => false // Inactivo hasta ser aprobado
            ]);


            // Crear el usuario asociado al veterinario (temporal)
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make(uniqid()), // Password temporal
                'userable_type' => 'App\Models\Veterinario',
                'userable_id' => $veterinario->id,
                'estado' => 'pendiente'
            ]);

            // Crear características temporales
            if ($request->experiencia || $request->descripcion) {
                CaracteristicasVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'anos_experiencia' => $request->experiencia ?? 0,
                    'descripcion' => $request->descripcion
                ]);
            }

            // Crear contactos temporales
            if ($request->telefono) {
                ContactoVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'tipo' => 'telefono',
                    'valor' => $request->telefono
                ]);
            }

            if ($request->emailContacto) {
                ContactoVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'tipo' => 'email',
                    'valor' => $request->emailContacto
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud enviada exitosamente. Espera la aprobación del administrador.',
                'data' => [
                    'solicitud_id' => $solicitud->id,
                    'veterinario_id' => $veterinario->id,
                    'redirect_to' => '/veterinario-pendiente'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método para que el administrador apruebe una solicitud
     */
    public function aprobarSolicitud($solicitudId)
    {
        try {
            DB::beginTransaction();

            $solicitud = SolicitudVeterinario::findOrFail($solicitudId);

            // Buscar el veterinario temporal asociado a esta solicitud
            $veterinario = Veterinario::where('matricula', $solicitud->matricula)
                                    ->where('estado', Veterinario::ESTADO_PENDIENTE)
                                    ->firstOrFail();

            // Actualizar el veterinario a estado aprobado
            $veterinario->update([
                'estado' => Veterinario::ESTADO_APROBADO,
                'activo' => true,
                'foto' => $solicitud->fotos[0] ?? $veterinario->foto // Actualizar foto si es necesario
            ]);

            // Actualizar el usuario a estado activo
            $user = User::where('email', $solicitud->email)->first();
            if ($user) {
                $user->update([
                    'estado' => 'activo'
                ]);
            }

            // Actualizar características si hay diferencias
            $caracteristicas = $veterinario->caracteristicas;
            if ($caracteristicas) {
                $caracteristicas->update([
                    'anos_experiencia' => $solicitud->anos_experiencia,
                    'descripcion' => $solicitud->descripcion
                ]);
            }

            // Actualizar estado de la solicitud
            $solicitud->update(['estado' => SolicitudVeterinario::ESTADO_APROBADO]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método para rechazar una solicitud
     */
    public function rechazarSolicitud($solicitudId)
    {
        try {
            DB::beginTransaction();

            $solicitud = SolicitudVeterinario::findOrFail($solicitudId);

            // Buscar el veterinario temporal asociado
            $veterinario = Veterinario::where('matricula', $solicitud->matricula)
                                    ->where('estado', Veterinario::ESTADO_PENDIENTE)
                                    ->first();

            if ($veterinario) {
                // Actualizar estado del veterinario a rechazado
                $veterinario->update([
                    'estado' => Veterinario::ESTADO_RECHAZADO,
                    'activo' => false
                ]);

                // Opcional: desactivar el usuario
                $user = User::where('email', $solicitud->email)->first();
                if ($user) {
                    $user->update(['estado' => 'inactivo']);
                }
            }

            // Actualizar estado de la solicitud
            $solicitud->update(['estado' => SolicitudVeterinario::ESTADO_RECHAZADO]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda las fotos y devuelve un array con las rutas
     */
    private function guardarFotos(Request $request)
    {
        $fotosGuardadas = [];
        
        for ($i = 0; $i < 6; $i++) {
            $fieldName = "foto{$i}";
            
            if ($request->hasFile($fieldName)) {
                $foto = $request->file($fieldName);
                $path = $foto->store('solicitudes-veterinarios', 'public');
                $fotosGuardadas[] = $path;
            }
        }
        
        return $fotosGuardadas;
    }

    /**
     * Obtener todas las solicitudes pendientes (para el admin)
     */
    public function obtenerSolicitudesPendientes()
    {
        $solicitudes = SolicitudVeterinario::where('estado', SolicitudVeterinario::ESTADO_PENDIENTE)
            ->orderBy('fecha_solicitud', 'desc')
            ->get()
            ->map(function ($solicitud) {
                // Convertir rutas de fotos a URLs completas
                if ($solicitud->fotos) {
                    $solicitud->fotos = array_map(function ($foto) {
                        return Storage::url($foto);
                    }, $solicitud->fotos);
                }
                return $solicitud;
            });

        return response()->json([
            'success' => true,
            'data' => $solicitudes
        ]);
    }

    /**
     * Obtener veterinarios por estado (para el admin)
     */
    public function obtenerVeterinariosPorEstado($estado)
    {
        $veterinarios = Veterinario::where('estado', $estado)
            ->with(['user', 'caracteristicas', 'mediosContacto'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $veterinarios
        ]);
    }
}