<?php
// app/Models/Notificacion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'contenido',
        'origen',
        'referencia_tipo',
        'referencia_id',
        'leida',
        'fecha_lectura',
        'activa'
    ];

    protected $casts = [
        'leida' => 'boolean',
        'activa' => 'boolean',
        'fecha_lectura' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Tipos de notificaciones - ¡AGREGADO!
    const TIPOS = [
        'ADVERTENCIA' => 'Advertencia',
        'SANCION' => 'Sanción',
        'SISTEMA' => 'Sistema',
        'MENSAJE' => 'Mensaje',
        'PROCEDIMIENTO' => 'Procedimiento',
        'DENUNCIA' => 'Denuncia',
        'OFERTA' => 'Oferta',
        'ADOPCION' => 'Adopción',
        'SOLICITUD' => 'Solicitud',
        'ALERTA' => 'Alerta'
    ];

    // Orígenes de notificaciones - ¡AGREGADO!
    const ORIGENES = [
        'SISTEMA' => 'Sistema',
        'USUARIO' => 'Usuario',
        'ADMINISTRADOR' => 'Administrador',
        'REFUGIO' => 'Refugio',
        'MUNICIPIO' => 'Municipio',
        'VETERINARIO' => 'Veterinario',
        'MODERADOR' => 'Moderador'
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Para mantener compatibilidad con código existente
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referencia()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeParaUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeRecientes($query, $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Métodos de instancia
    public function marcarComoLeida()
    {
        if (!$this->leida) {
            $this->update([
                'leida' => true,
                'fecha_lectura' => now()
            ]);
        }
    }

    public function desactivar()
    {
        $this->update(['activa' => false]);
    }

    public function getTipoFormateadoAttribute()
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getOrigenFormateadoAttribute()
    {
        return self::ORIGENES[$this->origen] ?? $this->origen;
    }

    public function getResumenAttribute()
    {
        return strlen($this->contenido) > 100 
            ? substr($this->contenido, 0, 100) . '...' 
            : $this->contenido;
    }

    // Métodos de creación estáticos
    public static function crearParaAdvertenciaSancion($sancion)
    {
        if ($sancion->tipo !== 'ADVERTENCIA') {
            return null;
        }
        
        if (!$sancion->relationLoaded('usuario')) {
            $sancion->load('usuario');
        }
        
        $usuario = $sancion->usuario;
        
        if (!$usuario) {
            return null;
        }
        
        // Cargar la denuncia relacionada si existe
        $denunciaInfo = '';
        if ($sancion->denuncia_id) {
            if (!$sancion->relationLoaded('denuncia')) {
                $sancion->load('denuncia');
            }
            
            if ($sancion->denuncia) {
                $denunciaInfo = "\n📋 **Denuncia relacionada:** #{$sancion->denuncia->id}";
            }
        }
        
        return self::create([
            'user_id' => $usuario->id,
            'tipo' => 'ADVERTENCIA',
            'titulo' => '⚠️ Has recibido una advertencia formal',
            'contenido' => self::generarContenidoAdvertencia($sancion, $denunciaInfo),
            'origen' => 'ADMINISTRADOR',
            'referencia_tipo' => 'sancion',
            'referencia_id' => $sancion->id,
            'leida' => false,
            'activa' => true
        ]);
    }

    public static function crearParaAdvertencia($userId, $motivo, $denunciaId = null)
    {
        return self::create([
            'user_id' => $userId,
            'tipo' => 'ADVERTENCIA',
            'titulo' => 'Advertencia por comportamiento inapropiado',
            'contenido' => "Has recibido una advertencia por: {$motivo}\n\n" .
                          "Por favor, revisa las normas de la comunidad.",
            'origen' => 'ADMINISTRADOR',
            'referencia_tipo' => $denunciaId ? 'denuncia' : null,
            'referencia_id' => $denunciaId,
            'leida' => false,
            'activa' => true
        ]);
    }

    public static function notificarEstadoDenuncia($denuncia)
    {
        $titulo = "Actualización en tu denuncia #{$denuncia->id}";
        $contenido = "El estado de tu denuncia ha cambiado a: {$denuncia->estado}\n" .
                     ($denuncia->notas_admin ? "\nNotas del administrador: {$denuncia->notas_admin}" : "");
        
        return self::create([
            'user_id' => $denuncia->usuario_id,
            'tipo' => 'DENUNCIA',
            'titulo' => $titulo,
            'contenido' => $contenido,
            'origen' => 'SISTEMA',
            'referencia_tipo' => 'denuncia',
            'referencia_id' => $denuncia->id,
            'leida' => false,
            'activa' => true
        ]);
    }

    public static function crearNotificacion($userId, $titulo, $contenido, $tipo = 'SISTEMA', $origen = 'SISTEMA', $referenciaTipo = null, $referenciaId = null)
    {
        return self::create([
            'user_id' => $userId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'contenido' => $contenido,
            'origen' => $origen,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
            'leida' => false,
            'activa' => true
        ]);
    }

    public static function notificarBloqueoUsuario($usuario, $bloqueado, $motivo = null)
    {
        $titulo = $bloqueado ? 'Tu cuenta ha sido bloqueada' : 'Tu cuenta ha sido desbloqueada';
        $contenido = $bloqueado 
            ? "Tu cuenta ha sido bloqueada por el siguiente motivo:\n\n{$motivo}\n\nPara más información, contacta al soporte."
            : "Tu cuenta ha sido desbloqueada. Ya puedes acceder a todas las funciones de la plataforma.";
        
        return self::create([
            'user_id' => $usuario->id,
            'tipo' => 'SISTEMA',
            'titulo' => $titulo,
            'contenido' => $contenido,
            'origen' => 'ADMINISTRADOR',
            'referencia_tipo' => 'usuario',
            'referencia_id' => $usuario->id,
            'leida' => false,
            'activa' => true
        ]);
    }

    public static function notificarNuevaOferta($mascotaId, $usuariosInteresados)
    {
        $mascota = \App\Models\Mascota::find($mascotaId);
        
        if (!$mascota) {
            return null;
        }
        
        $titulo = "Nueva oferta de adopción disponible";
        $contenido = "¡Hay una nueva oferta para adoptar a {$mascota->nombre}! Visita la plataforma para más detalles.";
        
        foreach ($usuariosInteresados as $userId) {
            self::create([
                'user_id' => $userId,
                'tipo' => 'OFERTA',
                'titulo' => $titulo,
                'contenido' => $contenido,
                'origen' => 'SISTEMA',
                'referencia_tipo' => 'mascota',
                'referencia_id' => $mascotaId,
                'leida' => false,
                'activa' => true
            ]);
        }
    }

    /**
     * Generar contenido específico para advertencia
     */
    private static function generarContenidoAdvertencia($sancion, $denunciaInfo = '')
    {
        $contenido = "Hola {$sancion->usuario->nombre},\n\n" .
                "Has recibido una **advertencia formal** de la administración por la siguiente razón:\n\n" .
                "📝 **Motivo:** {$sancion->razon}\n";
        
        if ($sancion->nivel) {
            $nivelesTexto = [
                'LEVE' => 'Leve',
                'MODERADO' => 'Moderado',
                'GRAVE' => 'Grave',
                'MUY_GRAVE' => 'Muy Grave'
            ];
            $contenido .= "⚖️ **Nivel de gravedad:** " . ($nivelesTexto[$sancion->nivel] ?? $sancion->nivel) . "\n";
        }
        
        if ($sancion->descripcion) {
            $contenido .= "\n📄 **Descripción detallada:**\n{$sancion->descripcion}\n";
        }
        
        $contenido .= $denunciaInfo .
                "\n📅 **Fecha de la advertencia:** {$sancion->fecha_inicio->format('d/m/Y H:i')}\n";
        
        if ($sancion->duracion_dias) {
            $contenido .= "⏱️ **Vigencia:** {$sancion->duracion_dias} días\n";
        }
        
        if ($sancion->restricciones && count($sancion->restricciones) > 0) {
            $restriccionesTexto = [
                'CREAR_OFERTAS' => 'Crear nuevas ofertas',
                'SOLICITAR_ADOPCION' => 'Solicitar adopciones',
                'PUBLICAR_COMENTARIOS' => 'Publicar comentarios',
                'ENVIAR_MENSAJES' => 'Enviar mensajes',
                'SUBIR_MASCOTAS' => 'Subir nuevas mascotas'
            ];
            
            $contenido .= "\n🚫 **Restricciones aplicadas:**\n";
            foreach ($sancion->restricciones as $restriccion) {
                $contenido .= "• " . ($restriccionesTexto[$restriccion] ?? $restriccion) . "\n";
            }
        }
        
        $contenido .= "\n⚠️ **Importante:** Esta es una advertencia formal. " .
                "Si continúas con el comportamiento inapropiado, podrías recibir sanciones más severas " .
                "como suspensiones temporales o bloqueos permanentes.\n\n" .
                "Si consideras que esta advertencia es un error o necesitas más información, " .
                "puedes contactar al equipo de soporte.\n\n" .
                "Saludos,\nEquipo de administración de la plataforma";
        
        return $contenido;
    }
    
    /**
     * Generar contenido para sanción
     */
    private static function generarContenidoSancion($sancion)
    {
        return "Hola {$sancion->usuario->nombre},\n\n" .
               "Has recibido una sanción por la siguiente razón:\n\n" .
               "📌 **Tipo:** {$sancion->tipo}\n" .
               "📝 **Razón:** {$sancion->razon}\n" .
               "⏱️ **Duración:** " . ($sancion->duracion_dias ? "{$sancion->duracion_dias} días" : "Indefinida") . "\n" .
               "📅 **Fecha inicio:** {$sancion->fecha_inicio->format('d/m/Y')}\n" .
               ($sancion->fecha_fin ? "📅 **Fecha fin:** {$sancion->fecha_fin->format('d/m/Y')}\n" : "") .
               "\n**Restricciones aplicadas:**\n" .
               ($sancion->restricciones ? "- " . implode("\n- ", $sancion->restricciones) : "Ninguna") .
               "\n\nSi consideras que esta sanción es un error, puedes contactar al soporte.\n\n" .
               "Saludos,\nEquipo de administración";
    } 
}