<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Chat;
use App\Models\User;

class Mensaje extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mensajes';
    protected $primaryKey = 'mensaje_id';

    protected $fillable = [
        'chat_id',
        'user_id',
        'contenido',
        'tipo',
        'url_adjunto',
        'leido',
        'leido_en'
    ];

    protected $casts = [
        'leido' => 'boolean',
        'leido_en' => 'datetime'
    ];

    // Relación con el chat
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'chat_id');
    }

    // Relación con el usuario que envió el mensaje
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Scope para mensajes del chat
    public function scopeDelChat($query, $chatId)
    {
        return $query->where('chat_id', $chatId)
            ->with('usuario')
            ->orderBy('created_at', 'asc');
    }

    // Marcar como leído
    public function marcarComoLeido()
    {
        if (!$this->leido) {
            $this->update([
                'leido' => true,
                'leido_en' => now()
            ]);
        }
    }

    // Formatear para el frontend
    public function formatearParaFrontend()
    {
        $usuario = $this->usuario;
        $userable = $usuario->userable ?? null;
        
        return [
            'id' => $this->mensaje_id,
            'chat_id' => $this->chat_id,
            'text' => $this->contenido,
            'sender' => auth()->id() == $this->user_id ? 'me' : 'them',
            'userId' => $this->user_id,
            'time' => $this->created_at,
            'leido' => $this->leido,
            'tipo' => $this->tipo,
            'adjunto' => $this->url_adjunto,
            'usuario' => $userable ? [
                'nombre' => $userable->nombre ?? $usuario->name,
                'img' => $userable->foto_perfil_url ?? 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png'
            ] : null
        ];
    }
}