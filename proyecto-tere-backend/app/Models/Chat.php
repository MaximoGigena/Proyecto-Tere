<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chats';
    protected $primaryKey = 'chat_id';

    protected $fillable = [
        'user1_id',
        'user2_id',
        'solicitud_id',
        'ultimo_mensaje',
        'ultimo_mensaje_en',
        'user1_deleted',
        'user2_deleted',
        'total_interacciones',
        'interacciones_usuario1',
        'interacciones_usuario2',
        'listo_para_adopcion',
        'fecha_habilitado_adopcion',
        'favoritos_por_usuario',
    ];

    protected $casts = [
        'user1_deleted' => 'boolean',
        'user2_deleted' => 'boolean',
        'ultimo_mensaje_en' => 'datetime',
        'fecha_habilitado_adopcion' => 'datetime',
        'favoritos_por_usuario' => 'array'
    ];

    // Relación con el primer usuario
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id', 'id');
    }

    // Relación con el segundo usuario
    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id', 'id');
    }

    // Relación con la solicitud
    public function solicitud()
    {
        return $this->belongsTo(SolicitudAdopcion::class, 'solicitud_id', 'idSolicitud');
    }

    // Relación con los mensajes
    public function mensajes()
    {
        return $this->hasMany(Mensaje::class, 'chat_id', 'chat_id')->latest();
    }

    // Relación con el último mensaje
    public function ultimoMensaje()
    {
        return $this->hasOne(Mensaje::class, 'chat_id', 'chat_id')->latest();
    }

    // Obtener el otro usuario del chat
    public function otroUsuario($userId)
    {
        if ($this->user1_id == $userId) {
            return $this->user2;
        }
        return $this->user1;
    }

    // Verificar si el usuario es participante
    public function esParticipante($userId)
    {
        return $this->user1_id == $userId || $this->user2_id == $userId;
    }

    // Marcar mensajes como leídos
    public function marcarComoLeido($userId)
    {
        $this->mensajes()
            ->where('user_id', '!=', $userId)
            ->where('leido', false)
            ->update([
                'leido' => true,
                'leido_en' => now()
            ]);
    }

    // Contar mensajes no leídos
    public function mensajesNoLeidos($userId)
    {
        return $this->mensajes()
            ->where('user_id', '!=', $userId)
            ->where('leido', false)
            ->count();
    }

    // Scope para chats activos del usuario
    public function scopeChatsUsuario($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('user1_id', $userId)
              ->where('user1_deleted', false)
              ->orWhere(function($q2) use ($userId) {
                  $q2->where('user2_id', $userId)
                     ->where('user2_deleted', false);
              });
        })
        ->with(['ultimoMensaje', 'user1.userable', 'user2.userable'])
        ->orderBy('ultimo_mensaje_en', 'desc');
    }

    // Obtener o crear chat entre usuarios
     public static function obtenerOCrearChat($user1Id, $user2Id, $solicitudId = null)
    {
        $chat = self::where(function($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user1Id)
                  ->where('user2_id', $user2Id);
        })
        ->orWhere(function($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user2Id)
                  ->where('user2_id', $user1Id);
        })
        ->when($solicitudId, function($query) use ($solicitudId) {
            $query->where('solicitud_id', $solicitudId);
        })
        ->first();

        if (!$chat) {
            $chat = self::create([
                'user1_id' => $user1Id,
                'user2_id' => $user2Id,
                'solicitud_id' => $solicitudId,
                'total_interacciones' => 0,
                'interacciones_usuario1' => 0,
                'interacciones_usuario2' => 0,
                'listo_para_adopcion' => false
            ]);
        }

        // Reactivar si fue eliminado
        if ($chat->user1_id == $user1Id && $chat->user1_deleted) {
            $chat->user1_deleted = false;
        }
        if ($chat->user2_id == $user2Id && $chat->user2_deleted) {
            $chat->user2_deleted = false;
        }
        
        $chat->save();

        return $chat;
    }


      /**
     * Obtener conteo de interacciones para un usuario específico
     */
    public function interaccionesDeUsuario($userId)
    {
        if ($this->user1_id == $userId) {
            return $this->interacciones_usuario1;
        } elseif ($this->user2_id == $userId) {
            return $this->interacciones_usuario2;
        }
        return 0;
    }

    /**
     * Obtener interacciones del otro usuario
     */
    public function interaccionesOtroUsuario($userId)
    {
        if ($this->user1_id == $userId) {
            return $this->interacciones_usuario2;
        } elseif ($this->user2_id == $userId) {
            return $this->interacciones_usuario1;
        }
        return 0;
    }

    /**
     * Verificar si el chat está listo para aprobación de adopción
     * Un chat está listo cuando ambos usuarios han intercambiado al menos 5 mensajes
     */
    public function estaListoParaAdopcion()
    {
        // Verificar que ambos usuarios tienen al menos 5 mensajes cada uno
        $minimoMensajesPorUsuario = min($this->interacciones_usuario1, $this->interacciones_usuario2);
        return $minimoMensajesPorUsuario >= 5;
    }

    /**
     * Actualizar conteo de interacciones cuando se envía un mensaje
     */
    public function actualizarConteoInteracciones($userId)
    {
        if ($this->user1_id == $userId) {
            $this->interacciones_usuario1 += 1;
        } elseif ($this->user2_id == $userId) {
            $this->interacciones_usuario2 += 1;
        } else {
            return; // Usuario no es participante
        }
        
        // Actualizar total (esto cuenta el "ping-pong" mínimo)
        $this->total_interacciones = min($this->interacciones_usuario1, $this->interacciones_usuario2) * 2;
        
        // Verificar si ahora está listo para adopción
        $estaListo = $this->estaListoParaAdopcion();
        if ($estaListo && !$this->listo_para_adopcion) {
            $this->listo_para_adopcion = true;
            $this->fecha_habilitado_adopcion = now();
        }
        
        $this->save();
        
        return [
            'interacciones_usuario' => $this->interaccionesDeUsuario($userId),
            'interacciones_otro_usuario' => $this->interaccionesOtroUsuario($userId),
            'total_interacciones' => $this->total_interacciones,
            'listo_para_adopcion' => $this->listo_para_adopcion,
            'mensajes_requeridos' => max(0, 5 - min($this->interacciones_usuario1, $this->interacciones_usuario2))
        ];
    }

    /**
     * Reiniciar conteo de interacciones (si es necesario)
     */
    public function reiniciarConteoInteracciones()
    {
        $this->total_interacciones = 0;
        $this->interacciones_usuario1 = 0;
        $this->interacciones_usuario2 = 0;
        $this->listo_para_adopcion = false;
        $this->fecha_habilitado_adopcion = null;
        $this->save();
    }

    /**
     * Obtener estadísticas de interacciones
     */
    public function obtenerEstadisticasInteracciones()
    {
        $minimo = min($this->interacciones_usuario1, $this->interacciones_usuario2);
        $maximo = max($this->interacciones_usuario1, $this->interacciones_usuario2);
        
        return [
            'total_mensajes_usuario1' => $this->interacciones_usuario1,
            'total_mensajes_usuario2' => $this->interacciones_usuario2,
            'total_interacciones_pingpong' => $minimo * 2,
            'mensajes_desequilibrio' => $maximo - $minimo,
            'listo_para_adopcion' => $this->listo_para_adopcion,
            'faltan_mensajes' => max(0, 5 - $minimo),
            'progreso' => min(100, ($minimo / 5) * 100)
        ];
    }

    /**
     * Obtener resumen de interacciones para la solicitud de adopción
     */
    public function obtenerResumenParaAdopcion()
    {
        return [
            'chat_id' => $this->chat_id,
            'solicitud_id' => $this->solicitud_id,
            'total_mensajes_intercambiados' => $this->interacciones_usuario1 + $this->interacciones_usuario2,
            'interacciones_validas' => min($this->interacciones_usuario1, $this->interacciones_usuario2),
            'cumple_requisito' => $this->estaListoParaAdopcion(),
            'detalle_usuarios' => [
                'usuario1' => [
                    'id' => $this->user1_id,
                    'mensajes_enviados' => $this->interacciones_usuario1,
                    'cumple_minimo' => $this->interacciones_usuario1 >= 5
                ],
                'usuario2' => [
                    'id' => $this->user2_id,
                    'mensajes_enviados' => $this->interacciones_usuario2,
                    'cumple_minimo' => $this->interacciones_usuario2 >= 5
                ]
            ],
            'estado' => $this->listo_para_adopcion ? 'HABILITADO' : 'PENDIENTE',
            'fecha_habilitacion' => $this->fecha_habilitado_adopcion,
            'recomendacion' => $this->estaListoParaAdopcion() 
                ? '✅ El chat cumple con el mínimo de interacciones requeridas para proceder con la adopción.'
                : '⚠️ Se requieren más interacciones entre ambos usuarios para habilitar la adopción.'
        ];
    }

   /**
     * Obtener el array de favoritos (inicializa si es null)
     */
    protected function getFavoritosArray()
    {
        return $this->favoritos_por_usuario ?? [];
    }

    /**
     * Toggle favorito para un usuario
     * @param int $userId
     * @return bool (nuevo estado: true = agregado, false = quitado)
     */
    public function toggleFavorito($userId)
    {
        // Verificar si el usuario es participante del chat
        if (!$this->esParticipante($userId)) {
            return false;
        }
        
        $favoritos = $this->getFavoritosArray();
        
        if (in_array($userId, $favoritos)) {
            // Quitar de favoritos
            $favoritos = array_values(array_diff($favoritos, [$userId]));
            $nuevoEstado = false;
        } else {
            // Agregar a favoritos
            $favoritos[] = $userId;
            $nuevoEstado = true;
        }
        
        $this->favoritos_por_usuario = $favoritos;
        $this->save();
        
        return $nuevoEstado;
    }

    /**
     * Verificar si el chat es favorito de un usuario
     * @param int $userId
     * @return bool
     */
    public function esFavoritoDe($userId)
    {
        $favoritos = $this->getFavoritosArray();
        return in_array($userId, $favoritos);
    }

    /**
     * Scope para obtener solo chats favoritos de un usuario
     * Usa JSON contiene en PostgreSQL
     */
    public function scopeFavoritosDeUsuario($query, $userId)
    {
        // Para PostgreSQL (que es tu caso según el error)
        return $query->whereRaw('jsonb_exists(favoritos_por_usuario::jsonb, ?)', [$userId]);
        
        // Alternativa para MySQL:
        // return $query->whereJsonContains('favoritos_por_usuario', $userId);
    }
}