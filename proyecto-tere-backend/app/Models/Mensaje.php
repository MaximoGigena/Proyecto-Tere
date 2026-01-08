<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
        'canal',
        'url_adjunto',
        'referencia_id',
        'referencia_tipo',
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

    // Relación polimórfica para referencias
    public function referencia()
    {
        return $this->morphTo(__FUNCTION__, 'referencia_tipo', 'referencia_id');
    }

    // Scope para mensajes del chat
    public function scopeDelChat($query, $chatId)
    {
        return $query->where('chat_id', $chatId)
            ->with(['usuario', 'referencia'])
            ->orderBy('created_at', 'asc');
    }

    // Scope para mensajes por tipo
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Scope para mensajes por canal
    public function scopePorCanal($query, $canal)
    {
        return $query->where('canal', $canal);
    }

    // Scope para mensajes con referencias
    public function scopeConReferencia($query, $tipo = null)
    {
        $query = $query->whereNotNull('referencia_id');
        
        if ($tipo) {
            $query->where('referencia_tipo', $tipo);
        }
        
        return $query;
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

    // Verificar si es un mensaje de sistema
    public function esSistema()
    {
        return $this->tipo === 'sistema';
    }

    // Verificar si es un mensaje con adjunto
    public function tieneAdjunto()
    {
        return !empty($this->url_adjunto);
    }

    // Verificar si tiene referencia
    public function tieneReferencia()
    {
        return !empty($this->referencia_id) && !empty($this->referencia_tipo);
    }

    // Obtener datos de la referencia (si existe)
    public function obtenerReferencia()
    {
        if ($this->tieneReferencia()) {
            return [
                'tipo' => $this->referencia_tipo,
                'id' => $this->referencia_id,
                'datos' => $this->referencia ? $this->referencia->toArray() : null
            ];
        }
        
        return null;
    }

    // Formatear para el frontend (versión mejorada)
    public function formatearParaFrontend()
    {
        $usuario = $this->usuario;
        $userable = $usuario->userable ?? null;
        $referencia = $this->obtenerReferencia();
        
        $formato = [
            'id' => $this->mensaje_id,
            'chat_id' => $this->chat_id,
            'text' => $this->contenido,
            'sender' => auth()->id() == $this->user_id ? 'me' : 'them',
            'userId' => $this->user_id,
            'time' => $this->created_at,
            'leido' => $this->leido,
            'tipo' => $this->tipo,
            'canal' => $this->canal,
            'adjunto' => $this->url_adjunto,
            'es_sistema' => $this->esSistema(),
            'tiene_adjunto' => $this->tieneAdjunto(),
            'referencia' => $referencia,
            'usuario' => $userable ? [
                'nombre' => $userable->nombre ?? $usuario->name,
                'img' => $userable->foto_perfil_url ?? 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png'
            ] : null
        ];
        
        // Si es mensaje de sistema, ocultamos el usuario
        if ($this->esSistema()) {
            $formato['usuario'] = null;
            $formato['sender'] = 'system';
        }
        
        return $formato;
    }
    
    // Crear un mensaje de sistema
    public static function crearSistema($chatId, $contenido, $tipo = 'sistema', $referencia = null)
    {
        $data = [
            'chat_id' => $chatId,
            'user_id' => null, // Los mensajes de sistema no tienen usuario
            'contenido' => $contenido,
            'tipo' => $tipo,
            'canal' => 'interno',
            'leido' => false
        ];
        
        if ($referencia) {
            $data['referencia_id'] = $referencia['id'];
            $data['referencia_tipo'] = $referencia['tipo'];
        }
        
        return self::create($data);
    }
    
    // Crear un mensaje externo (WhatsApp, Telegram, etc.)
    public static function crearExterno($chatId, $userId, $contenido, $canal, $urlAdjunto = null)
    {
        return self::create([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'contenido' => $contenido,
            'tipo' => 'externo',
            'canal' => $canal,
            'url_adjunto' => $urlAdjunto,
            'leido' => false
        ]);
    }
    
    // Crear un mensaje con referencia (factura, ficha clínica, etc.)
    public static function crearConReferencia($chatId, $userId, $contenido, $tipo, $referencia, $urlAdjunto = null)
    {
        return self::create([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'contenido' => $contenido,
            'tipo' => $tipo,
            'canal' => 'interno',
            'url_adjunto' => $urlAdjunto,
            'referencia_id' => $referencia['id'],
            'referencia_tipo' => $referencia['tipo'],
            'leido' => false
        ]);
    }
}