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
        'user2_deleted'
    ];

    protected $casts = [
        'user1_deleted' => 'boolean',
        'user2_deleted' => 'boolean',
        'ultimo_mensaje_en' => 'datetime'
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
                'solicitud_id' => $solicitudId
            ]);
        }

        // Reactivar si fue eliminado
        if ($chat->user1_id == $user1Id && $chat->user1_deleted) {
            $chat->user1_deleted = false;
            $chat->save();
        }
        if ($chat->user2_id == $user2Id && $chat->user2_deleted) {
            $chat->user2_deleted = false;
            $chat->save();
        }

        return $chat;
    }
}