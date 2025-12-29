<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Usuario;
use App\Models\CaracteristicasUsuario;
use App\Models\UbicacionUsuario;
use App\Models\BajaMascota;
use App\Models\OfertaAdopcion;
use App\Models\SolicitudAdopcion;
use App\Models\Mascota;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    
    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'old_data',
        'new_data',
        'changed_columns',
        'user_id',
        'ip_address',
        'user_agent'
    ];
    
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'changed_columns' => 'array',
        'created_at' => 'datetime'
    ];
    
    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Scope para filtrar por tabla
     */
    public function scopeForTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }
    
    /**
     * Scope para filtrar por registro específico
     */
    public function scopeForRecord($query, $tableName, $recordId)
    {
        return $query->where('table_name', $tableName)
                    ->where('record_id', $recordId);
    }
    
    /**
     * Scope para filtrar por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope para filtrar por acción
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
    
    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
    
    /**
     * Obtener los datos anteriores formateados
     */
    public function getOldDataFormattedAttribute()
    {
        if (!$this->old_data) return null;
        
        return collect($this->old_data)->map(function ($value, $key) {
            return [
                'campo' => $key,
                'valor' => $value,
                'tipo' => gettype($value)
            ];
        })->values()->toArray();
    }
    
    /**
     * Obtener los datos nuevos formateados
     */
    public function getNewDataFormattedAttribute()
    {
        if (!$this->new_data) return null;
        
        return collect($this->new_data)->map(function ($value, $key) {
            return [
                'campo' => $key,
                'valor' => $value,
                'tipo' => gettype($value)
            ];
        })->values()->toArray();
    }
    
    /**
     * Obtener cambios específicos (solo para UPDATE)
     */
    public function getChangesAttribute()
    {
        if ($this->action !== 'UPDATE' || !$this->old_data || !$this->new_data) {
            return [];
        }
        
        $changes = [];
        foreach ($this->old_data as $key => $oldValue) {
            if (isset($this->new_data[$key]) && $this->new_data[$key] != $oldValue) {
                $changes[$key] = [
                    'anterior' => $oldValue,
                    'nuevo' => $this->new_data[$key]
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Obtener el nombre del modelo relacionado
     */
    public function getRelatedModelAttribute()
    {
        $modelMappings = [
            'users' => User::class,
            'usuarios' => Usuario::class,
            'caracteristicas_usuarios' => CaracteristicasUsuario::class,
            'user_locations' => UbicacionUsuario::class,
            'usuario_contacto' => UbicacionUsuario::class,
            'bajas_mascotas' => BajaMascota::class,
            'ofertas_adopcion' => OfertaAdopcion::class,
            'solicitudes_adopcion' => SolicitudAdopcion::class,
            'mascotas' => Mascota::class,
            'caracteristicas_mascotas' => CaracteristicasMascota::class,
        ];
        
        return $modelMappings[$this->table_name] ?? null;
    }
}