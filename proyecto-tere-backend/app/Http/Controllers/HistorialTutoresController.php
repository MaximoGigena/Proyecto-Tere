<?php
// App\Http\Controllers\HistorialTutoresController.php

namespace App\Http\Controllers;

use App\Models\Mascota;
use App\Models\Usuario;
use App\Models\HistorialTransferenciaMascota;
use App\Models\OfertaAdopcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HistorialTutoresController extends Controller
{
    /**
     * Obtener el historial de tutores de una mascota
     */
    public function index($mascotaId)
    {
        try {
            $mascota = Mascota::with(['usuario', 'transferencias.tutorAnterior', 'transferencias.tutorNuevo'])
                ->findOrFail($mascotaId);
            
            // Verificar permisos (opcional - quien puede ver el historial)
            $user = Auth::user();
            $usuarioId = $user->userable->id ?? null;
            
            // Por ahora, cualquiera puede ver el historial de cualquier mascota
            // Si quieres restringir, puedes descomentar:
            // if ($mascota->usuario_id != $usuarioId && !$this->haTenidoMascota($usuarioId, $mascotaId)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'No tienes permiso para ver el historial de esta mascota'
            //     ], 403);
            // }
            
            $historial = $mascota->historialTutoresCompleto();
            
            // Añadir información adicional de contacto si el usuario actual es parte del historial
            $usuarioActualParticipa = $historial->contains('usuario_id', $usuarioId);
            
            $historialTransformado = $historial->map(function($item) use ($usuarioActualParticipa, $usuarioId) {
                // Determinar si el tutor es contactable
                // Por ahora, solo contactable si es el usuario actual
                // Más adelante implementarás la lógica de permisos de contacto
                $item['contactable'] = ($item['usuario_id'] == $usuarioId);
                
                // Formatear fechas para mostrar
                $item['adopcion'] = $item['fecha_inicio'];
                $item['desligo'] = $item['fecha_fin'];
                
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'mascota_id' => $mascota->id,
                    'mascota_nombre' => $mascota->nombre,
                    'tutor_actual_id' => $mascota->usuario_id,
                    'historial' => $historialTransformado->values(),
                    'cantidad_tutores' => $historial->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener historial de tutores: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial de tutores'
            ], 500);
        }
    }
    
    /**
     * Verificar si un usuario ha tenido esta mascota
     */
    private function haTenidoMascota($usuarioId, $mascotaId)
    {
        return HistorialTransferenciaMascota::where('mascota_id', $mascotaId)
            ->where(function($query) use ($usuarioId) {
                $query->where('tutor_anterior_id', $usuarioId)
                      ->orWhere('tutor_nuevo_id', $usuarioId);
            })
            ->exists();
    }
    
    /**
     * Obtener detalles de un tutor específico en el historial
     */
    public function tutorDetail($mascotaId, $usuarioId)
    {
        try {
            $mascota = Mascota::findOrFail($mascotaId);
            $usuario = Usuario::with(['caracteristicas', 'fotos'])->findOrFail($usuarioId);
            
            // Verificar si este usuario fue tutor de esta mascota
            $fueTutor = HistorialTransferenciaMascota::where('mascota_id', $mascotaId)
                ->where(function($query) use ($usuarioId) {
                    $query->where('tutor_anterior_id', $usuarioId)
                          ->orWhere('tutor_nuevo_id', $usuarioId);
                })
                ->exists();
            
            // Si no fue tutor y no es el tutor actual, denegar
            if (!$fueTutor && $mascota->usuario_id != $usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuario no fue tutor de esta mascota'
                ], 404);
            }
            
            // Obtener el período en que fue tutor
            $periodos = collect();
            
            if ($mascota->usuario_id == $usuarioId) {
                // Es el tutor actual
                $ultimaTransferencia = HistorialTransferenciaMascota::where('mascota_id', $mascotaId)
                    ->where('tutor_nuevo_id', $usuarioId)
                    ->latest('fecha_transferencia')
                    ->first();
                
                $periodos->push([
                    'fecha_inicio' => $ultimaTransferencia ? $ultimaTransferencia->fecha_transferencia->format('d/m/Y') : $mascota->created_at->format('d/m/Y'),
                    'fecha_fin' => 'Presente',
                    'es_actual' => true
                ]);
            }
            
            // Buscar períodos como tutor anterior
            $comoAnterior = HistorialTransferenciaMascota::where('mascota_id', $mascotaId)
                ->where('tutor_anterior_id', $usuarioId)
                ->with('tutorNuevo')
                ->get();
                
            foreach ($comoAnterior as $trans) {
                $periodos->push([
                    'fecha_inicio' => $trans->fecha_transferencia->format('d/m/Y'),
                    'fecha_fin' => 'Transferido a ' . ($trans->tutorNuevo->nombre ?? 'otro usuario'),
                    'es_actual' => false,
                    'motivo' => $trans->motivo,
                    'proceso_adopcion_id' => $trans->proceso_adopcion_id
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'edad' => $usuario->edad,
                        'foto_principal' => $usuario->foto_principal_url ?? null,
                        'caracteristicas' => $usuario->caracteristicas,
                        'tiempo_registro' => $this->formatearTiempoRegistro($usuario->created_at->diffInDays(now()), $usuario->created_at)
                    ],
                    'periodos_tutor' => $periodos->values()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles del tutor: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles del tutor'
            ], 500);
        }
    }
    
    private function formatearTiempoRegistro($dias, $fechaCreacion)
    {
        if ($dias === 0) {
            $horas = $fechaCreacion->diffInHours(now());
            if ($horas === 0) {
                $minutos = $fechaCreacion->diffInMinutes(now());
                return $minutos === 0 ? 'Hace unos segundos' : "Hace {$minutos} minutos";
            }
            return $horas === 1 ? 'Hace 1 hora' : "Hace {$horas} horas";
        } elseif ($dias === 1) {
            return 'Ayer';
        } elseif ($dias < 7) {
            return "Hace {$dias} días";
        } elseif ($dias < 30) {
            $semanas = floor($dias / 7);
            return "Hace {$semanas} semana" . ($semanas > 1 ? 's' : '');
        } elseif ($dias < 365) {
            $meses = floor($dias / 30);
            return "Hace {$meses} mes" . ($meses > 1 ? 'es' : '');
        } else {
            $anios = floor($dias / 365);
            return "Hace {$anios} año" . ($anios > 1 ? 's' : '');
        }
    }

    public function getHistorial($mascotaId)
    {
        try {
            $mascota = Mascota::with([
                'transferencias.tutorAnterior',  // Cambiado de historialTutores a transferencias
                'transferencias.tutorNuevo',     // Asegúrate de incluir las relaciones anidadas
                'usuario'                         // Para el tutor actual
            ])->findOrFail($mascotaId);
            
            // Obtener la oferta activa para esta mascota (si existe)
            $ofertaActiva = OfertaAdopcion::where('id_mascota', $mascotaId)
                ->whereIn('estado_oferta', ['publicada', 'en_proceso'])
                ->first();
            
            // Usar el método historialTutoresCompleto() que ya tienes en el modelo
            $historialCompleto = $mascota->historialTutoresCompleto();
            
            // Transformar el historial para agregar información de contacto
            $historial = $historialCompleto->map(function($item) use ($ofertaActiva) {
                // Verificar si este tutor es el actual y tiene permisos de contacto
                $mediosContacto = [];
                $contactable = false;
                
                // Si el tutor es el actual Y hay una oferta activa con permisos
                if ($item['es_actual'] && $ofertaActiva && $ofertaActiva->permiso_contacto_tutor) {
                    $contactable = true;
                    
                    // Obtener los medios de contacto seleccionados
                    $mediosSeleccionados = $ofertaActiva->medios_contacto_seleccionados ?? [];
                    
                    // Aquí deberías obtener el usuario para sus medios de contacto
                    $usuario = Usuario::with('contacto')->find($item['usuario_id']);
                    
                    if ($usuario && $usuario->contacto) {
                        if (in_array(1, $mediosSeleccionados) && $usuario->contacto->telefono) {
                            $mediosContacto[] = [
                                'id' => 1,
                                'tipo' => 'WhatsApp',
                                'valor' => $usuario->contacto->telefono,
                                'icono' => '📱',
                                'color' => 'green'
                            ];
                        }
                        
                        if (in_array(2, $mediosSeleccionados) && $usuario->contacto->email) {
                            $mediosContacto[] = [
                                'id' => 2,
                                'tipo' => 'Email',
                                'valor' => $usuario->contacto->email,
                                'icono' => '✉️',
                                'color' => 'orange'
                            ];
                        }
                        
                        if (in_array(3, $mediosSeleccionados) && $usuario->contacto->telegram_chat_id) {
                            $mediosContacto[] = [
                                'id' => 3,
                                'tipo' => 'Telegram',
                                'valor' => $usuario->contacto->telegram_chat_id,
                                'icono' => '📨',
                                'color' => 'blue'
                            ];
                        }
                    }
                }
                
                return [
                    'id' => $item['id'],
                    'nombre' => $item['nombre'],
                    'foto' => $item['foto'],
                    'adopcion' => $item['adopcion'],
                    'desligo' => $item['desligo'],
                    'motivo' => $item['motivo'],
                    'es_actual' => $item['es_actual'],
                    'es_primer_tutor' => $item['es_primer_tutor'],
                    'contactable' => $contactable,
                    'medios_contacto' => $mediosContacto,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'historial' => $historial->values(),
                    'mascota_nombre' => $mascota->nombre,
                    'especie' => $mascota->especie,
                    'edad_formateada' => $mascota->edad_formateada,
                    'foto_principal' => $mascota->foto_principal_url,
                    'cantidad_tutores' => $historial->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener historial: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el historial: ' . $e->getMessage()
            ], 500);
        }
    }
}