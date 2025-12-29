<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    use HasFactory;

    protected $table = 'sanciones';

    protected $fillable = [
        'usuario_id',
        'denuncia_id',
        'tipo',
        'nivel',
        'razon',
        'descripcion',
        'duracion_dias',
        'fecha_inicio',
        'fecha_fin',
        'restricciones',
        'estado',
        'notas_admin'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'restricciones' => 'array',
        'duracion_dias' => 'integer'
    ];

    // Tipos de sanciones
    const TIPOS = [
        'ADVERTENCIA',
        'SUSPENSION_TEMPORAL',
        'SUSPENSION_INDEFINIDA',
        'BLOQUEO_TEMPORAL',
        'BLOQUEO_PERMANENTE',
        'LIMITACION_FUNCIONES'
    ];

    // Niveles de gravedad
    const NIVELES = [
        'LEVE' => 1,
        'MODERADO' => 2,
        'GRAVE' => 3,
        'MUY_GRAVE' => 4
    ];

    // Restricciones disponibles
    const RESTRICCIONES = [
        'CREAR_OFERTAS',
        'SOLICITAR_ADOPCION',
        'PUBLICAR_COMENTARIOS',
        'ENVIAR_MENSAJES',
        'SUBIR_MASCOTAS',
        'ACCESO_PLATAFORMA'
    ];

    // Estados
    const ESTADOS = [
        'ACTIVA',
        'CUMPLIDA',
        'REVOCADA',
        'EXPIRADA'
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function denuncia()
    {
        return $this->belongsTo(Denuncia::class);
    }

    public function usuarioSancionado()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado', 'ACTIVA')
                    ->where(function($q) {
                        $q->whereNull('fecha_fin')
                          ->orWhere('fecha_fin', '>', now());
                    });
    }

    public function scopeParaUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    // Métodos de instancia
    public function estaActiva()
    {
        if ($this->estado !== 'ACTIVA') {
            return false;
        }

        if ($this->fecha_fin && $this->fecha_fin->lt(now())) {
            $this->update(['estado' => 'EXPIRADA']);
            return false;
        }

        return true;
    }

    public function aplicarRestricciones()
    {
        if (!$this->estaActiva()) {
            return;
        }

        $user = $this->usuario;
        
        // Aplicar restricciones según el tipo de sanción
        switch ($this->tipo) {
            case 'SUSPENSION_TEMPORAL':
            case 'BLOQUEO_TEMPORAL':
                $user->estado = 'suspendido';
                break;
            case 'BLOQUEO_PERMANENTE':
                $user->estado = 'bloqueado';
                break;
            case 'LIMITACION_FUNCIONES':
                // Las restricciones específicas se manejan en middleware
                break;
        }

        $user->save();
    }

    public function revocar()
    {
        $this->estado = 'REVOCADA';
        $this->save();

        // Restaurar estado del usuario si es necesario
        $user = $this->usuario;
        if ($user->estado === 'suspendido' || $user->estado === 'bloqueado') {
            $user->estado = 'activo';
            $user->save();
        }
    }

    public function verificarExpiracion()
    {
        if ($this->fecha_fin && $this->fecha_fin->lt(now()) && $this->estado === 'ACTIVA') {
            $this->estado = 'EXPIRADA';
            $this->save();

            // Restaurar usuario si estaba suspendido
            $user = $this->usuario;
            if ($user->estado === 'suspendido') {
                $user->estado = 'activo';
                $user->save();
            }
        }
    }

    // Añade estos métodos al modelo Sancion

    /**
     * Obtener el nombre legible del tipo
     */
    public function getTipoLegibleAttribute()
    {
        $tipos = [
            'ADVERTENCIA' => 'Advertencia',
            'SUSPENSION_TEMPORAL' => 'Suspensión Temporal',
            'SUSPENSION_INDEFINIDA' => 'Suspensión Indefinida',
            'BLOQUEO_TEMPORAL' => 'Bloqueo Temporal',
            'BLOQUEO_PERMANENTE' => 'Bloqueo Permanente',
            'LIMITACION_FUNCIONES' => 'Limitación de Funciones'
        ];
        
        return $tipos[$this->tipo] ?? $this->tipo;
    }

    /**
     * Obtener el nombre legible del nivel
     */
    public function getNivelLegibleAttribute()
    {
        $niveles = [
            'LEVE' => 'Leve',
            'MODERADO' => 'Moderado',
            'GRAVE' => 'Grave',
            'MUY_GRAVE' => 'Muy Grave'
        ];
        
        return $niveles[$this->nivel] ?? $this->nivel;
    }

    /**
     * Obtener restricciones formateadas
     */
    public function getRestriccionesLegiblesAttribute()
    {
        $restricciones = [
            'CREAR_OFERTAS' => 'Crear ofertas',
            'SOLICITAR_ADOPCION' => 'Solicitar adopción',
            'PUBLICAR_COMENTARIOS' => 'Publicar comentarios',
            'ENVIAR_MENSAJES' => 'Enviar mensajes',
            'SUBIR_MASCOTAS' => 'Subir mascotas',
            'ACCESO_PLATAFORMA' => 'Acceso a la plataforma'
        ];
        
        return array_map(function ($restriccion) use ($restricciones) {
            return $restricciones[$restriccion] ?? $restriccion;
        }, $this->restricciones ?? []);
    }

    /**
     * Verificar si la sanción es permanente
     */
    public function getEsPermanenteAttribute()
    {
        return $this->tipo === 'BLOQUEO_PERMANENTE' || is_null($this->fecha_fin);
    }

    /**
     * Calcular días restantes
     */
    public function getDiasRestantesAttribute()
    {
        if (!$this->fecha_fin || $this->es_permanente) {
            return null;
        }
        
        $hoy = now();
        $fin = $this->fecha_fin;
        
        if ($fin->lt($hoy)) {
            return 0;
        }
        
        return $hoy->diffInDays($fin, false);
    }
}