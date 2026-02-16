<?php
// database/migrations/2026_01_24_000004_create_medical_procedures_audit_triggers.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear triggers para procedimientos médicos principales
        $this->createMedicalProcedureTriggers();
        
        // Crear triggers para tablas de tipos
        $this->createMedicalTypesTriggers();
        
        // Crear triggers para tablas de relación
        $this->createMedicalRelationTriggers();
    }

    public function down(): void
    {
        $this->dropMedicalProcedureTriggers();
    }
    
    private function createMedicalProcedureTriggers(): void
    {
        $medicalTables = [
            'alergias' => 'alergias_audit_trigger',
            'cirugias' => 'cirugias_audit_trigger',
            'cuidados_paliativos' => 'cuidados_paliativos_audit_trigger',
            'desparasitaciones' => 'desparasitaciones_audit_trigger',
            'diagnosticos' => 'diagnosticos_audit_trigger',
            'farmacos' => 'farmacos_audit_trigger',
            'revisiones' => 'revisiones_audit_trigger',
            'terapias' => 'terapias_audit_trigger',
            'vacunas' => 'vacunas_audit_trigger',
        ];
        
        foreach ($medicalTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'procedimientos médicos');
        }
    }
    
    private function createMedicalTypesTriggers(): void
    {
        $typeTables = [
            'tipos_alergia' => 'tipos_alergia_audit_trigger',
            'tipos_cirugia' => 'tipos_cirugia_audit_trigger',
            'tipos_paliativo' => 'tipos_paliativo_audit_trigger',
            'tipos_desparasitacion' => 'tipos_desparasitacion_audit_trigger',
            'tipos_diagnostico' => 'tipos_diagnostico_audit_trigger',
            'tipos_farmaco' => 'tipos_farmaco_audit_trigger',
            'tipos_revision' => 'tipos_revision_audit_trigger',
            'tipos_terapia' => 'tipos_terapia_audit_trigger',
            'tipos_vacuna' => 'tipos_vacuna_audit_trigger',
        ];
        
        foreach ($typeTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'tipos médicos');
        }
    }
    
    private function createMedicalRelationTriggers(): void
    {
        $relationTables = [
            'procesos_medicos' => 'procesos_medicos_audit_trigger',
            'procedimiento_diagnosticos' => 'procedimiento_diagnosticos_audit_trigger',
            'archivo_cirugias' => 'archivo_cirugias_audit_trigger',
            'archivos_terapia' => 'archivos_terapia_audit_trigger',
            'farmacos_asociados' => 'farmacos_asociados_audit_trigger',
            'revision_diagnosticos' => 'revision_diagnosticos_audit_trigger',
            'procedimiento_diagnostico_historial' => 'procedimiento_diagnostico_historial_audit_trigger',
        ];
        
        foreach ($relationTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'relaciones médicas');
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
    
    private function dropMedicalProcedureTriggers(): void
    {
        $tables = [
            // Procedimientos principales
            'alergias', 'cirugias', 'cuidados_paliativos', 'desparasitaciones', 'diagnosticos',
            'farmacos', 'revisiones', 'terapias', 'vacunas',
            
            // Tipos
            'tipos_alergia', 'tipos_cirugia', 'tipos_paliativo', 'tipos_desparasitacion',
            'tipos_diagnostico', 'tipos_farmaco', 'tipos_revision', 'tipos_terapia', 'tipos_vacuna',
            
            // Relaciones
            'procesos_medicos', 'procedimiento_diagnosticos', 'archivo_cirugias',
            'archivos_terapia', 'farmacos_asociados', 'revision_diagnosticos',
            'procedimiento_diagnostico_historial',
        ];
        
        foreach ($tables as $table) {
            $triggerName = $table . '_audit_trigger';
            DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
            echo "✅ Trigger eliminado para tabla médica: {$table}\n";
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