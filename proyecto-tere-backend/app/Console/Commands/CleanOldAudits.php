<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Carbon\Carbon;

class CleanOldAudits extends Command
{
    protected $signature = 'audit:clean {--days=90 : Número de días a mantener}';
    protected $description = 'Eliminar registros de auditoría antiguos';
    
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Eliminando auditorías anteriores a: {$cutoffDate->format('Y-m-d')}");
        
        $deleted = AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("¡Listo! Se eliminaron {$deleted} registros de auditoría.");
        
        return 0;
    }
}