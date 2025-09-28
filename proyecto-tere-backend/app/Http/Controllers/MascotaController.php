<?php

namespace App\Http\Controllers;

use App\Models\Mascota;
use App\Models\CaracteristicasMascota;
use App\Models\MascotaFoto; 
use App\Models\MotivoBaja;
use App\Models\BajaMascota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MascotaController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos obligatorios
        $request->validate([
            'nombre' => 'required|string|max:255',
            'especie' => 'required|in:perro,gato,otro',
            'edad' => 'required|integer|min:0',
            'unidad_edad' => 'required|in:Dias,Meses,Años',
            'sexo' => 'required|in:macho,hembra',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp'
        ]);

        $usuario = Auth::user()->userable;

        // Crear la mascota
        $mascota = Mascota::create([
            'nombre' => $request->nombre,
            'especie' => $request->especie,
            'edad' => $request->edad,
            'unidad_edad' => $request->unidad_edad,
            'sexo' => $request->sexo,
            'usuario_id' => $usuario->id
        ]);

        // Crear las características opcionales si existen
        if ($mascota->id) {
            $caracteristicas = CaracteristicasMascota::create([
                'mascota_id' => $mascota->id,
                'tamano' => $request->tamano, // 👈 sin ñ
                'pelaje' => $request->pelaje,
                'alimentacion' => $request->alimentacion,
                'energia' => $request->energia,
                'comportamiento_animales' => $request->comportamiento_animales,
                'comportamiento_ninos' => $request->comportamiento_ninos,
                'personalidad' => $request->personalidad,
                'descripcion' => $request->descripcion
            ]);

        }

        // Procesar las fotos
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $index => $foto) {
                if ($foto->isValid()) {
                    $path = $foto->store('mascotas/' . $mascota->id, 'public');

                    MascotaFoto::create([
                        'mascota_id' => $mascota->id,
                        'ruta_foto' => $path,
                        'es_principal' => $index === 0 // 👈 primera foto será la principal
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Mascota registrada correctamente',
            'mascota' => $mascota,
            'caracteristicas' => $caracteristicas ?? null
        ], 201);
    }

    public function show($id)
    {
        $mascota = Mascota::with(['caracteristicas', 'fotos'])
            ->where('id', $id)
            ->where('usuario_id', Auth::user()->userable->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'mascota' => $mascota
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validar datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'especie' => 'required|in:perro,gato,otro',
            'edad' => 'required|integer|min:0',
            'unidad_edad' => 'required|in:Dias,Meses,Años',
            'sexo' => 'required|in:macho,hembra',
            'nuevas_fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'fotos_eliminar.*' => 'nullable|integer'
        ]);

        $usuario = Auth::user()->userable;

        // Buscar la mascota
        $mascota = Mascota::where('id', $id)
            ->where('usuario_id', $usuario->id)
            ->firstOrFail();

        // Actualizar datos básicos
        $mascota->update([
            'nombre' => $request->nombre,
            'especie' => $request->especie,
            'edad' => $request->edad,
            'unidad_edad' => $request->unidad_edad,
            'sexo' => $request->sexo,
        ]);

        // Actualizar características
        $caracteristicas = CaracteristicasMascota::updateOrCreate(
            ['mascota_id' => $mascota->id],
            [
                'tamano' => $request->tamano,
                'pelaje' => $request->pelaje,
                'alimentacion' => $request->alimentacion,
                'energia' => $request->energia,
                'comportamiento_animales' => $request->comportamiento_animales,
                'comportamiento_ninos' => $request->comportamiento_ninos,
                'personalidad' => $request->personalidad,
                'descripcion' => $request->descripcion
            ]
        );

        // Eliminar fotos marcadas para eliminación
        if ($request->has('fotos_eliminar')) {
            foreach ($request->fotos_eliminar as $fotoId) {
                $foto = MascotaFoto::where('id', $fotoId)
                    ->where('mascota_id', $mascota->id)
                    ->first();
                
                if ($foto) {
                    // Eliminar archivo físico
                    Storage::disk('public')->delete($foto->ruta_foto);
                    $foto->delete();
                }
            }
        }

        // Agregar nuevas fotos
        if ($request->hasFile('nuevas_fotos')) {
            foreach ($request->file('nuevas_fotos') as $foto) {
                if ($foto->isValid()) {
                    $path = $foto->store('mascotas/' . $mascota->id, 'public');

                    MascotaFoto::create([
                        'mascota_id' => $mascota->id,
                        'ruta_foto' => $path,
                        'es_principal' => false // Las nuevas fotos no son principales por defecto
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Mascota actualizada correctamente',
            'mascota' => $mascota->fresh(['caracteristicas', 'fotos'])
        ]);
    }

    public function index()
    {
        // 🔑 Obtener el usuario real (tabla usuarios)
        $user = Auth::user();
        $usuario = $user->userable;
        
        $mascotas = Mascota::with(['caracteristicas', 'fotos', 'baja'])
            ->where('usuario_id', $usuario->id)
            ->whereNull('deleted_at') // Solo mascotas activas
            ->get();

        return response()->json([
            'success' => true,
            'mascotas' => $mascotas
        ]);
    }

   // En MascotaController.php - CORREGIR ESTE MÉTODO
    public function darDeBaja(Request $request, $id)
    {
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID de mascota inválido'
            ], 422);
        }

        // Validar los datos de la baja
        $request->validate([
            'motivo_baja_id' => 'required|integer|exists:motivos_baja,id',
            'observacion' => 'nullable|string|max:500'
        ]);

        $usuario = Auth::user()->userable;

        try {
            // Buscar la mascota del usuario
            $mascota = Mascota::where('id', $id)
                ->where('usuario_id', $usuario->id)
                ->first();

            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Verificar que la mascota no esté ya dada de baja
            if ($mascota->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La mascota ya está dada de baja'
                ], 409);
            }

            // Usar el método del modelo para dar de baja
            $resultado = $mascota->darDeBaja(
                $request->motivo_baja_id,
                $request->observacion,
                $usuario->id
            );

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mascota dada de baja correctamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja la mascota'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error al dar de baja mascota ID ' . $id . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    // En MascotaController.php
    public function obtenerMotivosBaja()
    {
        try {
            $motivos = MotivoBaja::where('activo', true)
                ->get(['id', 'descripcion']);

            return response()->json([
                'success' => true,
                'motivos' => $motivos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener motivos de baja: ' . $e->getMessage());
            
            // Fallback con motivos por defecto
            $motivosPorDefecto = [
                ['id' => 1, 'descripcion' => 'Fallecimiento de la mascota'],
                ['id' => 2, 'descripcion' => 'Extraviada'],
                ['id' => 3, 'descripcion' => 'Adoptada por otra persona'],
                ['id' => 4, 'descripcion' => 'Traslado de domicilio'],
                ['id' => 5, 'descripcion' => 'Problemas de convivencia'],
            ];

            return response()->json([
                'success' => true,
                'motivos' => $motivosPorDefecto
            ]);
        }
    }
}
