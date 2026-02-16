<?php
// database/migrations/2026_01_24_000005_create_veterinary_audit_triggers.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear triggers para veterinarios principales
        $this->createVeterinaryMainTriggers();
        
        // Crear triggers para características y contacto
        $this->createVeterinaryDetailTriggers();
        
        // Nota: Las tablas de tipos ya están cubiertas en la migración médica
        // No es necesario duplicarlas aquí
    }

    public function down(): void
    {
        $this->dropVeterinaryTriggers();
    }
    
    private function createVeterinaryMainTriggers(): void
    {
        $mainTables = [
            'veterinarios' => 'veterinarios_audit_trigger',
            'solicitudes_veterinarios' => 'solicitudes_veterinarios_audit_trigger',
            'centros_veterinarios' => 'centros_veterinarios_audit_trigger',
        ];
        
        foreach ($mainTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'veterinarios principales');
        }
    }
    
    private function createVeterinaryDetailTriggers(): void
    {
        $detailTables = [
            'caracteristicas_veterinarios' => 'caracteristicas_veterinarios_audit_trigger',
            'contacto_veterinarios' => 'contacto_veterinarios_audit_trigger',
        ];
        
        foreach ($detailTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'detalles veterinarios');
        }
    }
    
    private function createTriggerForTable(string $table, string $triggerName, string $category): void
    {
        if (!$this->tableExists($table)) {
            echo "⚠️  Tabla no existe en categoría {$category}: {$table} (saltando...)\n";
            return;
        }
        
        DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
        
        DB::unprepared("
            CREATE TRIGGER {$triggerName}
            AFTER INSERT OR UPDATE OR DELETE ON {$table}
            FOR EACH ROW 
            EXECUTE FUNCTION audit_trigger_function();
        ");
        
        echo "✅ Trigger creado para tabla {$category}: {$table}\n";
    }
    
    private function dropVeterinaryTriggers(): void
    {
        $tables = [
            'veterinarios',
            'solicitudes_veterinarios',
            'centros_veterinarios',
            'caracteristicas_veterinarios',
            'contacto_veterinarios',
        ];
        
        foreach ($tables as $table) {
            $triggerName = $table . '_audit_trigger';
            DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
            echo "✅ Trigger eliminado para tabla veterinaria: {$table}\n";
        }
    }
    
    private function tableExists(string $table): bool
    {
        $result = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            ) as exists
        ", [$table]);
        
        return (bool) $result->exists;
    }
};