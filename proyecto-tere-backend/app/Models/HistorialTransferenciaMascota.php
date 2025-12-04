<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialTransferenciaMascota extends Model
{
    protected $table = 'historial_transferencias_mascotas';
    protected $primaryKey = 'id_transferencia';
    
    protected $fillable = [
        'mascota_id',
        'tutor_anterior_id',
        'tutor_nuevo_id',
        'solicitud_adopcion_id',
        'proceso_adopcion_id',
        'fecha_transferencia',
        'motivo',
        'observaciones',
        'datos_adicionales'
    ];
    
    protected $casts = [
        'fecha_transferencia' => 'datetime',
        'datos_adicionales' => 'array'
    ];
    
    /**
     * Relación con la mascota
     */
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
    }
    
    /**
     * Relación con el tutor anterior
     */
    public function tutorAnterior(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'tutor_anterior_id');
    }
    
    /**
     * Relación con el tutor nuevo
     */
    public function tutorNuevo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'tutor_nuevo_id');
    }
    
    /**
     * Relación con la solicitud de adopción
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAdopcion::class, 'solicitud_adopcion_id', 'idSolicitud');
    }
    
    /**
     * Relación con el proceso de adopción
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(ProcesoAdopcion::class, 'proceso_adopcion_id', 'id_proceso');
    }
    
    /**
     * Scope para transferencias por mascota
     */
    public function scopeDeMascota($query, $mascotaId)
    {
        return $query->where('mascota_id', $mascotaId);
    }
    
    /**
     * Scope para transferencias por tutor
     */
    public function scopeDeTutor($query, $tutorId)
    {
        return $query->where('tutor_anterior_id', $tutorId)
                     ->orWhere('tutor_nuevo_id', $tutorId);
    }
    
    /**
     * Scope para transferencias recientes
     */
    public function scopeRecientes($query, $dias = 30)
    {
        return $query->where('fecha_transferencia', '>=', now()->subDays($dias));
    }
    
    /**
     * Obtener transferencia en formato legible
     */
    public function getDescripcionAttribute(): string
    {
        $tutorAnterior = $this->tutorAnterior->nombre ?? 'Desconocido';
        $tutorNuevo = $this->tutorNuevo->nombre ?? 'Desconocido';
        $fecha = $this->fecha_transferencia->format('d/m/Y H:i');
        
        return "{$tutorAnterior} → {$tutorNuevo} ({$fecha}) - " . ucfirst($this->motivo);
    }
}