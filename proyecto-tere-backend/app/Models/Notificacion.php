<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
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

    // Tipos de notificaciones
    const TIPOS = [
        'ADVERTENCIA' => 'Advertencia',
        'SANCION' => 'Sanción',
        'SISTEMA' => 'Sistema',
        'MENSAJE' => 'Mensaje',
        'PROCEDIMIENTO' => 'Procedimiento',
        'DENUNCIA' => 'Denuncia',
        'OFERTA' => 'Oferta',
        'SOLICITUD' => 'Solicitud',
        'ALERTA' => 'Alerta'
    ];

    // Orígenes de notificaciones
    const ORIGENES = [
        'SISTEMA' => 'Sistema',
        'USUARIO' => 'Usuario',
        'ADMINISTRADOR' => 'Administrador',
        'VETERINARIO' => 'Veterinario',
        'MODERADOR' => 'Moderador'
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function referencia()
    {
        return $this->morphTo();
    }

    // Scopes útiles
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeParaUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
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

    /**
     * Crear notificación para advertencia como sanción
     */
    public static function crearParaAdvertenciaSancion($sancion)
    {
        // Verificar que sea una advertencia
        if ($sancion->tipo !== 'ADVERTENCIA') {
            Log::warning('Intento de crear notificación para sanción que no es advertencia:', [
                'tipo' => $sancion->tipo,
                'sancion_id' => $sancion->id
            ]);
            return null;
        }
        
        // Cargar la relación usuario si no está cargada
        if (!$sancion->relationLoaded('usuario')) {
            $sancion->load('usuario');
        }
        
        $usuario = $sancion->usuario;
        
        if (!$usuario) {
            Log::error('Usuario no encontrado para sanción:', [
                'sancion_id' => $sancion->id,
                'usuario_id' => $sancion->usuario_id
            ]);
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
            'usuario_id' => $usuario->id,
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

    /**
     * Generar contenido específico para advertencia (método estático)
     */
    private static function generarContenidoAdvertencia($sancion, $denunciaInfo = '')
    {
        $contenido = "Hola {$sancion->usuario->nombre},\n\n" .
                "Has recibido una **advertencia formal** de la administración por la siguiente razón:\n\n" .
                "📝 **Motivo:** {$sancion->razon}\n";
        
        // Agregar nivel de gravedad si está disponible
        if ($sancion->nivel) {
            $nivelesTexto = [
                'LEVE' => 'Leve',
                'MODERADO' => 'Moderado',
                'GRAVE' => 'Grave',
                'MUY_GRAVE' => 'Muy Grave'
            ];
            $contenido .= "⚖️ **Nivel de gravedad:** " . ($nivelesTexto[$sancion->nivel] ?? $sancion->nivel) . "\n";
        }
        
        // Agregar descripción si existe
        if ($sancion->descripcion) {
            $contenido .= "\n📄 **Descripción detallada:**\n{$sancion->descripcion}\n";
        }
        
        $contenido .= $denunciaInfo .
                "\n📅 **Fecha de la advertencia:** {$sancion->fecha_inicio->format('d/m/Y H:i')}\n";
        
        // Agregar duración si es temporal
        if ($sancion->duracion_dias) {
            $contenido .= "⏱️ **Vigencia:** {$sancion->duracion_dias} días\n";
        }
        
        // Agregar información sobre restricciones si existen
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
     * Generar contenido para sanción (método estático)
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
    
    /**
     * Crear notificación para advertencia
     */
    public static function crearParaAdvertencia($usuarioId, $motivo, $denunciaId = null)
    {
        return self::create([
            'usuario_id' => $usuarioId,
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
    
    /**
     * Notificar cambio en denuncia
     */
    public static function notificarEstadoDenuncia($denuncia)
    {
        $titulo = "Actualización en tu denuncia #{$denuncia->id}";
        $contenido = "El estado de tu denuncia ha cambiado a: {$denuncia->estado}\n" .
                     ($denuncia->notas_admin ? "\nNotas del administrador: {$denuncia->notas_admin}" : "");
        
        return self::create([
            'usuario_id' => $denuncia->usuario_id,
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
    
    /**
     * Crear notificación genérica
     */
    public static function crearNotificacion($usuarioId, $titulo, $contenido, $tipo = 'SISTEMA', $origen = 'SISTEMA', $referenciaTipo = null, $referenciaId = null)
    {
        return self::create([
            'usuario_id' => $usuarioId,
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
    
    /**
     * Notificar bloqueo/desbloqueo de usuario
     */
    public static function notificarBloqueoUsuario($usuario, $bloqueado, $motivo = null)
    {
        $titulo = $bloqueado ? 'Tu cuenta ha sido bloqueada' : 'Tu cuenta ha sido desbloqueada';
        $contenido = $bloqueado 
            ? "Tu cuenta ha sido bloqueada por el siguiente motivo:\n\n{$motivo}\n\nPara más información, contacta al soporte."
            : "Tu cuenta ha sido desbloqueada. Ya puedes acceder a todas las funciones de la plataforma.";
        
        return self::create([
            'usuario_id' => $usuario->id,
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
    
    /**
     * Notificar nueva oferta de adopción
     */
    public static function notificarNuevaOferta($mascotaId, $usuariosInteresados)
    {
        $mascota = \App\Models\Mascota::find($mascotaId);
        
        if (!$mascota) {
            return null;
        }
        
        $titulo = "Nueva oferta de adopción disponible";
        $contenido = "¡Hay una nueva oferta para adoptar a {$mascota->nombre}! Visita la plataforma para más detalles.";
        
        foreach ($usuariosInteresados as $usuarioId) {
            self::create([
                'usuario_id' => $usuarioId,
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
}