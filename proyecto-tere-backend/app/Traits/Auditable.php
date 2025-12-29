<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    /**
     * Obtener el historial de auditoría del modelo
     */
    public function auditHistory()
    {
        return AuditLog::forTable($this->getTable())
                      ->forRecord($this->getTable(), $this->getKey())
                      ->with('user')
                      ->orderBy('created_at', 'desc')
                      ->get();
    }
    
    /**
     * Obtener los últimos cambios
     */
    public function latestAudit()
    {
        return AuditLog::forTable($this->getTable())
                      ->forRecord($this->getTable(), $this->getKey())
                      ->with('user')
                      ->latest()
                      ->first();
    }
    
    /**
     * Obtener cambios por acción específica
     */
    public function auditByAction($action)
    {
        return AuditLog::forTable($this->getTable())
                      ->forRecord($this->getTable(), $this->getKey())
                      ->byAction($action)
                      ->with('user')
                      ->orderBy('created_at', 'desc')
                      ->get();
    }
    
    /**
     * Verificar si el modelo ha sido modificado recientemente
     */
    public function wasRecentlyModified($minutes = 5)
    {
        $latest = $this->latestAudit();
        
        if (!$latest) return false;
        
        return $latest->created_at->gt(now()->subMinutes($minutes));
    }
}