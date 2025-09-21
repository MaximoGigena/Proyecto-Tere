<?php

namespace App\Http\Controllers;

use App\Models\Veterinario;
use App\Models\CaracteristicasVeterinario;
use App\Models\ContactoVeterinario;
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
            'email' => 'required|email|unique:users,email',
            'matricula' => 'required|string|unique:veterinarios,matricula',
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

        // Iniciar transacción para asegurar la consistencia de datos
        try {
            DB::beginTransaction();

            // Crear el veterinario
            $veterinario = Veterinario::create([
                'nombre_completo' => $request->nombre,
                'matricula' => $request->matricula, // ✅ Quitar email_profesional
                'especialidad' => $request->especialidad,
                'foto' => $this->guardarFotos($request)
            ]);

            $user = User::create([
                'email' => $request->email, // ✅ Email va en users
                'password' => Hash::make($request->password ?? uniqid()), // Permitir password o generar uno
                'userable_type' => 'App\Models\Veterinario',
                'userable_id' => $veterinario->id,
            ]);

            // Crear características del veterinario
            if ($request->experiencia || $request->descripcion) {
                CaracteristicasVeterinario::create([
                    'veterinario_id' => $veterinario->id,
                    'anos_experiencia' => $request->experiencia ?? 0,
                    'descripcion' => $request->descripcion
                ]);
            }

            // Crear contactos del veterinario
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
                'message' => 'Veterinario registrado exitosamente',
                'data' => $veterinario
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el veterinario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda las fotos del veterinario y devuelve la ruta de la primera foto
     */
    private function guardarFotos(Request $request)
    {
        $fotoPrincipal = null;
        
        for ($i = 0; $i < 6; $i++) {
            $fieldName = "foto{$i}";
            
            if ($request->hasFile($fieldName)) {
                $foto = $request->file($fieldName);
                $path = $foto->store('veterinarios', 'public');
                
                // La primera foto se considera la principal
                if ($i === 0) {
                    $fotoPrincipal = $path;
                }
                
                // Aquí puedes guardar las rutas de las fotos adicionales en otra tabla si es necesario
            }
        }
        
        return $fotoPrincipal;
    }
}
