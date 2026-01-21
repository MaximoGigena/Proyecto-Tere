<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcesoAdopcion extends Model
{
    
    protected $table = 'proceso_adopcion';
    protected $primaryKey = 'id_proceso';
    
    protected $fillable = [
        'id_oferta',
        'id_solicitud',
        'id_usuario_tutor',
        'id_usuario_adoptante',
        'estado_proceso',
        'fecha_inicio',
        'fecha_fin',
        'confirmacion_tutor',
        'confirmacion_adoptante',
        'notas_tutor',
        'notas_adoptante',
        'motivo_rechazo',
        'puntuacion_experiencia',
        'comentario_final'
    ];
    
    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'confirmacion_tutor' => 'boolean',
        'confirmacion_adoptante' => 'boolean',
        'puntuacion_experiencia' => 'integer'
    ];
    
    protected $attributes = [
        'estado_proceso' => 'iniciado'
    ];
    
    /**
     * Relación con la oferta de adopción
     */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(OfertaAdopcion::class, 'id_oferta', 'id_oferta');
    }
    
    /**
     * Relación con la solicitud
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAdopcion::class, 'id_solicitud', 'idSolicitud');
    }
    
    /**
     * Relación con el tutor original
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_tutor');
    }
    
    /**
     * Relación con el adoptante
     */
    public function adoptante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_adoptante');
    }
    
    /**
     * Relación con seguimientos del proceso
     */
    public function seguimientos(): HasMany
    {
        return $this->hasMany(SeguimientoAdopcion::class, 'id_proceso');
    }
    
    /**
     * Eventos del ciclo de vida
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($proceso) {
            $proceso->fecha_inicio = now();
        });
        
        static::updating(function ($proceso) {
            if ($proceso->isDirty('estado_proceso') && 
                in_array($proceso->estado_proceso, ['finalizado', 'rechazado', 'cancelado'])) {
                $proceso->fecha_fin = now();
            }
        });
    }
    
    /**
     * Verificar si el proceso puede avanzar
     */
    public function puedeAvanzar(string $nuevoEstado): bool
    {
        $flujoValido = [
            'iniciado' => ['entrevista', 'cancelado'],
            'entrevista' => ['evaluacion', 'rechazado', 'cancelado'],
            'evaluacion' => ['aprobado', 'rechazado', 'cancelado'],
            'aprobado' => ['finalizado', 'cancelado'],
            'rechazado' => [],
            'cancelado' => [],
            'finalizado' => []
        ];
        
        return in_array($nuevoEstado, $flujoValido[$this->estado_proceso] ?? []);
    }
    
    /**
     * Verificar si ambas partes confirmaron
     */
    public function confirmacionesCompletadas(): bool
    {
        return $this->confirmacion_tutor && $this->confirmacion_adoptante;
    }
    
    /**
     * Marcar como finalizado cuando ambas partes confirman
     */
    public function intentarFinalizar(): bool
    {
        if ($this->estado_proceso === 'aprobado' && $this->confirmacionesCompletadas()) {
            $this->estado_proceso = 'finalizado';
            $this->save();
            return true;
        }
        return false;
    }
}