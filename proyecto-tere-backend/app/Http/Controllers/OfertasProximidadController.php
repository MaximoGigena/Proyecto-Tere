<?php

namespace App\Http\Controllers;

use App\Models\OfertaAdopcion;
use App\Models\UbicacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfertasProximidadController extends Controller
{
    /**
     * Obtener TODAS las ofertas ordenadas por proximidad
     */
    public function obtenerPorProximidad(Request $request)
    {
        try {
            $user = Auth::user();
            $usuario = $user->userable; // Esto es el modelo Usuario
            $usuarioId = $usuario->id; // ID del Usuario (no del User)
            
            Log::info('=== OBTENER TODAS LAS OFERTAS POR PROXIMIDAD ===');
            Log::info('Usuario (User):', ['user_id' => $user->id, 'email' => $user->email]);
            Log::info('Usuario (Usuario):', ['usuario_id' => $usuarioId, 'tipo' => get_class($usuario)]);
            
            // 1. Obtener ubicación del usuario actual
            $ubicacionUsuario = $user->ubicacionActual;
            
            $latUsuario = null;
            $lonUsuario = null;
            $tieneUbicacion = false;
            
            if ($ubicacionUsuario && $ubicacionUsuario->latitude && $ubicacionUsuario->longitude) {
                $latUsuario = $ubicacionUsuario->latitude;
                $lonUsuario = $ubicacionUsuario->longitude;
                $tieneUbicacion = true;
                
                Log::info('Ubicación usuario:', [
                    'latitude' => $latUsuario,
                    'longitude' => $lonUsuario,
                    'city' => $ubicacionUsuario->city,
                    'state' => $ubicacionUsuario->state
                ]);
            } else {
                Log::warning('Usuario no tiene ubicación registrada o no tiene coordenadas');
            }
            
            // 2. Obtener TODAS las ofertas EXCLUYENDO LAS DEL USUARIO
            $query = OfertaAdopcion::with([
                'mascota.fotos',
                'mascota.caracteristicas',
                'mascota.edadRelacion',
                'mascota.usuario.user.ubicacionActual',
                'usuarioResponsable'
            ])
            ->where('estado_oferta', 'publicada')
            // ✅ CORRECCIÓN: Usar $usuarioId (el ID del Usuario)
            ->where('id_usuario_responsable', '!=', $usuarioId);
            
            // ✅ Agregar log para depuración
            Log::info('Excluyendo ofertas del usuario:', [
                'user_id' => $user->id,
                'usuario_id' => $usuarioId,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // 3. Obtener todas las ofertas SIN FILTRAR
            $ofertas = $query->get();
            
            Log::info('Total ofertas encontradas (TODAS): ' . $ofertas->count());
            
            // 4. Procesar cada oferta y calcular distancia (si hay ubicación del usuario)
            $ofertasProcesadas = collect();
            
            foreach ($ofertas as $oferta) {
                try {
                    // ✅ VERIFICACIÓN EXTRA: Confirmar que no es del usuario
                    $esDelUsuario = $oferta->id_usuario_responsable == $usuarioId;
                    if ($esDelUsuario) {
                        Log::warning('Oferta del usuario encontrada después del filtro!', [
                            'oferta_id' => $oferta->id_oferta,
                            'id_usuario_responsable' => $oferta->id_usuario_responsable,
                            'usuario_id' => $usuarioId
                        ]);
                        continue; // Saltar esta oferta
                    }
                    
                    $distanciaKm = null;
                    $ubicacionTutor = null;
                    
                    // Obtener ubicación del tutor
                    if ($oferta->mascota && 
                        $oferta->mascota->usuario && 
                        $oferta->mascota->usuario->user && 
                        $oferta->mascota->usuario->user->ubicacionActual) {
                        
                        $ubicacion = $oferta->mascota->usuario->user->ubicacionActual;
                        
                        if ($ubicacion->latitude && $ubicacion->longitude) {
                            $ubicacionTutor = [
                                'latitude' => $ubicacion->latitude,
                                'longitude' => $ubicacion->longitude,
                                'city' => $ubicacion->city,
                                'state' => $ubicacion->state,
                                'country' => $ubicacion->country,
                            ];
                            
                            // ✅ CALCULAR DISTANCIA SOLO si el usuario tiene ubicación
                            if ($tieneUbicacion) {
                                $distanciaKm = $this->calcularDistancia(
                                    $latUsuario,
                                    $lonUsuario,
                                    $ubicacion->latitude,
                                    $ubicacion->longitude
                                );
                            }
                        }
                    }
                    
                    $ofertasProcesadas->push([
                        'oferta' => $oferta,
                        'distancia_km' => $distanciaKm,
                        'ubicacion_tutor' => $ubicacionTutor,
                        'tiene_ubicacion_tutor' => $ubicacionTutor !== null
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error procesando oferta: ' . $e->getMessage());
                    continue;
                }
            }
        
            
            // 5. ✅ CORRECCIÓN: NO FILTRAR NUNCA por distancia
            // Solo ordenar si hay ubicación del usuario
            if ($tieneUbicacion) {
                // Ordenar por distancia (las más cercanas primero), las sin distancia van al final
                $ofertasOrdenadas = $ofertasProcesadas->sortBy(function($item) {
                    return $item['distancia_km'] ?? 999999; // Las sin distancia van al final
                })->values();
            } else {
                // Si no tiene ubicación, ordenar por fecha
                $ofertasOrdenadas = $ofertasProcesadas->sortByDesc(function($item) {
                    return $item['oferta']->created_at;
                })->values();
            }
            
            // 6. Formatear respuesta
            $ofertasFormateadas = $ofertasOrdenadas->map(function($item) {
                return $this->formatearOferta($item['oferta'], $item['distancia_km'], $item['ubicacion_tutor']);
            });
            
            Log::info('Total ofertas a mostrar: ' . $ofertasFormateadas->count());
            
            // 7. Preparar respuesta
            $response = [
                'success' => true,
                'data' => $ofertasFormateadas,
                'total' => $ofertasFormateadas->count(),
                'estadisticas' => [
                    'con_distancia' => $ofertasProcesadas->where('distancia_km', '!=', null)->count(),
                    'sin_distancia' => $ofertasProcesadas->where('distancia_km', null)->count(),
                    'con_ubicacion_tutor' => $ofertasProcesadas->where('tiene_ubicacion_tutor', true)->count(),
                    'total' => $ofertasProcesadas->count(),
                    'tiene_ubicacion_usuario' => $tieneUbicacion
                ]
            ];
            
            // Agregar ubicación del usuario solo si tiene
            if ($tieneUbicacion) {
                $response['ubicacion_usuario'] = [
                    'latitude' => $latUsuario,
                    'longitude' => $lonUsuario,
                    'city' => $ubicacionUsuario->city,
                    'state' => $ubicacionUsuario->state,
                    'country' => $ubicacionUsuario->country,
                ];
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Error en obtenerPorProximidad: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ofertas por proximidad',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Calcular distancia entre dos puntos en kilómetros usando fórmula haversine
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en kilómetros
        
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }
    
    /**
     * Formatear oferta con información de distancia
     */
    private function formatearOferta($oferta, $distanciaKm = null, $ubicacionTutor = null)
    {
        $mascota = $oferta->mascota;
        
        // Determinar nivel de proximidad
        $nivelProximidad = null;
        $textoDistancia = 'Sin ubicación';
        
        if ($distanciaKm !== null) {
            $nivelProximidad = $this->determinarNivelProximidad($distanciaKm);
            $textoDistancia = $this->formatearDistancia($distanciaKm);
        } elseif ($ubicacionTutor) {
            $textoDistancia = 'Distancia no calculada';
        }
        
        // Formatear ubicación
        $ubicacionTexto = $this->formatearUbicacionTexto($ubicacionTutor);
        
        // Obtener foto principal
        $fotoPrincipalUrl = null;
        if ($mascota->fotos && $mascota->fotos->isNotEmpty()) {
            $fotoPrincipal = $mascota->fotos->first();
            $fotoPrincipalUrl = $fotoPrincipal->url;
        }
        
        // Determinar rango etario
        $rangoEtario = $this->determinarRangoEtario($mascota);
        
        return [
            'id_oferta' => $oferta->id_oferta,
            'mascota' => [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre ?? 'Sin nombre',
                'especie' => $mascota->especie ?? 'Desconocido',
                'sexo' => $mascota->sexo ?? 'Desconocido',
                'edad_formateada' => $mascota->edad_formateada ?? 'Edad no especificada',
                'foto_principal_url' => $fotoPrincipalUrl,
                'caracteristicas' => $mascota->caracteristicas ?? [],
                'rango_etario' => $rangoEtario,
                'ubicacion_texto' => $ubicacionTexto,
            ],
            'distancia' => $textoDistancia,
            'distancia_km' => $distanciaKm !== null ? round($distanciaKm, 1) : null,
            'nivel_proximidad' => $nivelProximidad,
            'icono_proximidad' => $this->getIconoProximidad($nivelProximidad),
            'created_at' => $oferta->created_at ? $oferta->created_at->format('d/m/Y H:i') : 'Fecha no disponible',
            'tiene_distancia' => $distanciaKm !== null,
            'tiene_ubicacion_tutor' => $ubicacionTutor !== null,
        ];
    }
    
    /**
     * Determinar nivel de proximidad
     */
    private function determinarNivelProximidad($distanciaKm)
    {
        if ($distanciaKm === null) return null;
        
        if ($distanciaKm <= 10) return 'muy_cerca';      // 0-10km
        if ($distanciaKm <= 50) return 'cerca';          // 11-50km
        if ($distanciaKm <= 150) return 'moderado';      // 51-150km
        if ($distanciaKm <= 500) return 'lejos';         // 151-500km
        if ($distanciaKm <= 1000) return 'muy_lejos';    // 501-1000km
        return 'otra_provincia';                        // >1000km
    }
    
    /**
     * Determinar rango etario de la mascota
     */
    private function determinarRangoEtario($mascota)
    {
        if (!$mascota->edadRelacion || $mascota->edadRelacion->dias === null) {
            return 'Adulto';
        }
        
        $dias = $mascota->edadRelacion->dias;
        
        if ($mascota->especie === 'perro' || $mascota->especie === 'gato') {
            if ($dias < 180) return 'Cachorro';        // Menos de 6 meses
            if ($dias < 365) return 'Joven';           // 6 meses a 1 año
            if ($dias < 365 * 7) return 'Adulto';      // 1 a 7 años
            return 'Senior';                           // Más de 7 años
        }
        
        return 'Adulto';
    }
    
    /**
     * Formatear distancia para mostrar
     */
    private function formatearDistancia($distanciaKm)
    {
        if ($distanciaKm === null) {
            return 'Sin ubicación';
        }
        
        if ($distanciaKm < 1) {
            $metros = round($distanciaKm * 1000);
            return "{$metros} m";
        } elseif ($distanciaKm < 10) {
            return round($distanciaKm, 1) . " km";
        } else {
            return round($distanciaKm) . " km";
        }
    }
    
    /**
     * Formatear texto de ubicación
     */
    private function formatearUbicacionTexto($ubicacion)
    {
        if (!$ubicacion) {
            return 'Ubicación no disponible';
        }
        
        $parts = [];
        if (!empty($ubicacion['city'])) {
            $parts[] = $ubicacion['city'];
        }
        if (!empty($ubicacion['state']) && $ubicacion['state'] !== $ubicacion['city']) {
            $parts[] = $ubicacion['state'];
        }
        
        return implode(', ', $parts) ?: 'Ubicación no disponible';
    }
    
    /**
     * Obtener icono según nivel de proximidad
     */
    private function getIconoProximidad($nivel)
    {
        $iconos = [
            'muy_cerca' => '📍',      // En tu ciudad (0-10km)
            'cerca' => '🏙️',         // Cercano (11-50km)
            'moderado' => '🗺️',       // Moderado (51-150km)
            'lejos' => '🌎',          // Lejos (151-500km)
            'muy_lejos' => '🚗',      // Muy lejos (501-1000km)
            'otra_provincia' => '✈️', // Otra provincia (>1000km)
        ];
        
        return $iconos[$nivel] ?? '📍';
    }
}