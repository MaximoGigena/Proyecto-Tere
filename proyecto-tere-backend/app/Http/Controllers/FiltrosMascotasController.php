<?php

namespace App\Http\Controllers;

use App\Models\OfertaAdopcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FiltrosMascotasController extends Controller
{
    /**
     * Definir rangos etarios por especie (en días)
     */
    public static function getRangosEtariosPorEspecie()
    {
        return [
            'caninos' => [
                'cachorro' => ['min' => 0, 'max' => 365],    // 0-1 año
                'joven'    => ['min' => 366, 'max' => 1095], // 1-3 años
                'adulto'   => ['min' => 1096, 'max' => 3285], // 3-9 años
                'abuelo'   => ['min' => 3286, 'max' => 99999] // +10 años
            ],
            'felinos' => [
                'cachorro' => ['min' => 0, 'max' => 365],    // 0-1 año
                'joven'    => ['min' => 366, 'max' => 1460], // 1-4 años
                'adulto'   => ['min' => 1461, 'max' => 3650], // 4-10 años
                'abuelo'   => ['min' => 3651, 'max' => 99999] // +10 años
            ],
            'equinos' => [
                'cachorro' => ['min' => 0, 'max' => 730],    // 0-2 años
                'joven'    => ['min' => 731, 'max' => 1825], // 2-5 años
                'adulto'   => ['min' => 1826, 'max' => 5475], // 5-15 años
                'abuelo'   => ['min' => 5476, 'max' => 99999] // +15 años
            ],
            'bovinos' => [
                'cachorro' => ['min' => 0, 'max' => 365],    // 0-1 año
                'joven'    => ['min' => 366, 'max' => 1095], // 1-3 años
                'adulto'   => ['min' => 1096, 'max' => 2920], // 3-8 años
                'abuelo'   => ['min' => 2921, 'max' => 99999] // +8 años
            ],
            'aves' => [
                'cachorro' => ['min' => 0, 'max' => 365],    // 0-1 año
                'joven'    => ['min' => 366, 'max' => 1095], // 1-3 años
                'adulto'   => ['min' => 1096, 'max' => 2920], // 3-8 años
                'abuelo'   => ['min' => 2921, 'max' => 99999] // +8 años
            ],
            'peces' => [
                'cachorro' => ['min' => 0, 'max' => 183],    // 0-0.5 años
                'joven'    => ['min' => 184, 'max' => 548],  // 0.5-1.5 años
                'adulto'   => ['min' => 549, 'max' => 1460], // 1.5-4 años
                'abuelo'   => ['min' => 1461, 'max' => 99999] // +4 años
            ],
            'otro' => [
                'cachorro' => ['min' => 0, 'max' => 365],    // Default: 0-1 año
                'joven'    => ['min' => 366, 'max' => 1095], // Default: 1-3 años
                'adulto'   => ['min' => 1096, 'max' => 3285], // Default: 3-9 años
                'abuelo'   => ['min' => 3286, 'max' => 99999] // Default: +10 años
            ]
        ];
    }

    /**
     * Determinar el rango etario según la especie y días de vida
     */
    public static function determinarRangoEtario($especie, $dias)
    {
        if ($dias === null) {
            return 'Desconocido';
        }
        
        $rangos = self::getRangosEtariosPorEspecie();
        
        // Normalizar el nombre de la especie (minúsculas)
        $especie = strtolower($especie);
        
        // Si la especie no existe en los rangos, usar 'otro'
        if (!isset($rangos[$especie])) {
            $especie = 'otro';
        }
        
        $rangosEspecie = $rangos[$especie];
        
        foreach ($rangosEspecie as $rango => $valores) {
            if ($dias >= $valores['min'] && $dias <= $valores['max']) {
                return ucfirst($rango);
            }
        }
        
        return 'Adulto'; // Valor por defecto
    }

    /**
     * Aplicar filtros a la consulta de ofertas
     */
    public function aplicarFiltros($query, Request $request)
    {
        // Filtro por especie (puede ser múltiple)
        if ($request->has('especie') && !empty($request->especie)) {
            $this->aplicarFiltroEspecie($query, $request->especie);
        }
        
        // Filtro por sexo (puede ser múltiple)
        if ($request->has('sexo') && !empty($request->sexo)) {
            $this->aplicarFiltroSexo($query, $request->sexo);
        }
        
        // Filtro por rango de edad
        if ($request->has('rangos_edad') && !empty($request->rangos_edad)) {
            $this->aplicarFiltroEdad($query, $request->rangos_edad);
        }
        
        return $query;
    }

    /**
     * Aplicar filtro de especie
     */
    private function aplicarFiltroEspecie($query, $especies)
    {
        Log::info('=== APLICANDO FILTRO ESPECIE ===');
        Log::info('Datos recibidos:', ['especies_raw' => $especies]);
        
        if (is_string($especies)) {
            $especies = json_decode($especies, true) ?? [$especies];
        }
        
        // 🔥 MAPEAR PLURAL A SINGULAR
        $mapeoPluralSingular = [
            'caninos' => 'canino',
            'felinos' => 'felino', 
            'equinos' => 'equino',
            'bovinos' => 'bovino',
            'aves' => 'ave',
            'peces' => 'pez',
            'otros' => 'otro',
            'otro' => 'otro' // por si acaso
        ];
        
        $especiesNormalizadas = array_map(function($especie) use ($mapeoPluralSingular) {
            $especie = strtolower($especie);
            return $mapeoPluralSingular[$especie] ?? $especie;
        }, (array)$especies);
        
        Log::info('Especies después de normalizar:', ['especies' => $especiesNormalizadas]);
        
        $query->whereHas('mascota', function($q) use ($especiesNormalizadas) {
            $q->whereIn('especie', $especiesNormalizadas);
        });
        
        return $query;
    }

    /**
     * Aplicar filtro de sexo
     */
    private function aplicarFiltroSexo($query, $sexos)
    {
        if (is_string($sexos)) {
            $sexos = json_decode($sexos, true) ?? [$sexos];
        }
        
        $sexos = array_map('strtolower', (array)$sexos);
        
        $query->whereHas('mascota', function($q) use ($sexos) {
            $q->whereIn('sexo', $sexos);
        });
    }

    /**
     * Aplicar filtro de edad considerando diferentes rangos por especie
     */
    private function aplicarFiltroEdad($query, $rangosEdadSolicitados)
    {
        // Intentar decodificar si es JSON
        if (is_string($rangosEdadSolicitados)) {
            $rangosEdadSolicitados = json_decode($rangosEdadSolicitados, true);
        }
        
        if (!is_array($rangosEdadSolicitados) || empty($rangosEdadSolicitados)) {
            return;
        }
        
        $query->where(function($q) use ($rangosEdadSolicitados) {
            // Para cada rango solicitado
            foreach ($rangosEdadSolicitados as $rangoSolicitado) {
                if (is_string($rangoSolicitado)) {
                    // Es un nombre de rango (cachorro, joven, etc.)
                    $this->agregarCondicionEdadPorNombre($q, $rangoSolicitado);
                } elseif (is_array($rangoSolicitado) && isset($rangoSolicitado['min']) && isset($rangoSolicitado['max'])) {
                    // Es un rango específico (min, max)
                    $this->agregarCondicionEdadPorRango($q, $rangoSolicitado['min'], $rangoSolicitado['max']);
                }
            }
        });
    }

    /**
     * Agregar condición para un nombre de rango (cachorro, joven, etc.)
     */
    private function agregarCondicionEdadPorNombre($query, $nombreRango)
    {
        $nombreRango = strtolower($nombreRango);
        $rangosPorEspecie = self::getRangosEtariosPorEspecie();
        
        $query->orWhere(function($q) use ($nombreRango, $rangosPorEspecie) {
            // Para cada especie, verificar si tiene este rango
            foreach ($rangosPorEspecie as $especie => $rangos) {
                if (isset($rangos[$nombreRango])) {
                    $rango = $rangos[$nombreRango];
                    
                    $q->orWhere(function($subq) use ($especie, $rango) {
                        $subq->whereHas('mascota', function($mq) use ($especie) {
                            $mq->where('especie', $especie);
                        })
                        ->whereHas('mascota.edadRelacion', function($eq) use ($rango) {
                            $eq->whereBetween('dias', [$rango['min'], $rango['max']]);
                        });
                    });
                }
            }
        });
    }

    /**
     * Agregar condición para un rango específico (min, max)
     */
    private function agregarCondicionEdadPorRango($query, $min, $max)
    {
        $query->orWhereHas('mascota.edadRelacion', function($eq) use ($min, $max) {
            $eq->whereBetween('dias', [$min, $max]);
        });
    }

    /**
     * Obtener las especies disponibles para filtrado
     */
    public function obtenerEspeciesDisponibles()
    {
        try {
            $especies = OfertaAdopcion::join('mascotas', 'ofertas_adopcion.id_mascota', '=', 'mascotas.id')
                ->where('ofertas_adopcion.estado_oferta', 'publicada')
                ->select('mascotas.especie')
                ->distinct()
                ->orderBy('mascotas.especie')
                ->pluck('especie');
            
            return response()->json([
                'success' => true,
                'data' => $especies
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo especies disponibles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener especies'
            ], 500);
        }
    }

    /**
     * Obtener opciones de filtro para el frontend
     */
    public function obtenerOpcionesFiltro()
    {
        try {
            $opciones = [
                'especies' => [
                    'Caninos' => 'caninos',
                    'Felinos' => 'felinos',
                    'Equinos' => 'equinos',
                    'Bovinos' => 'bovinos',
                    'Aves' => 'aves',
                    'Peces' => 'peces',
                    'Otro' => 'otro'
                ],
                'rangos_edad' => [
                    'Cachorro' => 'cachorro',
                    'Joven' => 'joven',
                    'Adulto' => 'adulto',
                    'Abuelo' => 'abuelo'
                ],
                'sexos' => [
                    'Macho' => 'macho',
                    'Hembra' => 'hembra'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $opciones
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo opciones de filtro: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones de filtro'
            ], 500);
        }
    }

    /**
     * Método auxiliar para determinar rangos de edad basados en meses
     */
    public static function getRangosPorEdadEnMeses($rangosSeleccionados)
    {
        // Si no hay rangos seleccionados, devolver null
        if (empty($rangosSeleccionados)) {
            return null;
        }

        $rangos = [];
        
        foreach ($rangosSeleccionados as $rango) {
            // Convertir a minúsculas para coincidencia
            $rango = strtolower($rango);
            
            // Definir rangos aproximados en días (se usarán como fallback)
            switch ($rango) {
                case 'cachorro':
                    $rangos[] = ['min' => 0, 'max' => 365]; // 0-12 meses
                    break;
                case 'joven':
                    $rangos[] = ['min' => 366, 'max' => 1095]; // 1-3 años
                    break;
                case 'adulto':
                    $rangos[] = ['min' => 1096, 'max' => 3285]; // 3-9 años
                    break;
                case 'abuelo':
                    $rangos[] = ['min' => 3286, 'max' => 99999]; // +10 años
                    break;
            }
        }
        
        return $rangos;
    }
}