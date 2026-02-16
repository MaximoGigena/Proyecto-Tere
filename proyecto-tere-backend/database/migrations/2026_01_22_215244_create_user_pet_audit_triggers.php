<?php
// database/migrations/2026_01_24_000003_create_user_pet_audit_triggers.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar que la función existe
        $this->ensureAuditFunctionExists();
        
        // Crear triggers para usuarios
        $this->createUserTriggers();
        
        // Crear triggers para mascotas
        $this->createPetTriggers();
        
        // Crear triggers para adopción
        $this->createAdoptionTriggers();
    }

    public function down(): void
    {
        $this->dropUserPetTriggers();
    }
    
    private function ensureAuditFunctionExists(): void
    {
        $functionExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM pg_proc 
                WHERE proname = 'audit_trigger_function'
            ) as exists
        ");
        
        if (!$functionExists->exists) {
            throw new Exception('La función audit_trigger_function no existe. Ejecuta primero la migración 000002.');
        }
    }
    
    private function createUserTriggers(): void
    {
        $userTables = [
            'users' => 'users_audit_trigger',
            'usuarios' => 'usuarios_audit_trigger',
            'caracteristicas_usuarios' => 'caracteristicas_usuarios_audit_trigger',
            'user_locations' => 'user_locations_audit_trigger',
            'usuario_contacto' => 'usuario_contacto_audit_trigger',
        ];
        
        foreach ($userTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'usuarios');
        }
    }
    
    private function createPetTriggers(): void
    {
        $petTables = [
            'mascotas' => 'mascotas_audit_trigger',
            'caracteristicas_mascotas' => 'caracteristicas_mascotas_audit_trigger',
            'bajas_mascotas' => 'bajas_mascotas_audit_trigger',
        ];
        
        foreach ($petTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'mascotas');
        }
    }
    
    private function createAdoptionTriggers(): void
    {
        $adoptionTables = [
            'ofertas_adopcion' => 'ofertas_adopcion_audit_trigger',
            'solicitudes_adopcion' => 'solicitudes_adopcion_audit_trigger',
        ];
        
        foreach ($adoptionTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName, 'adopción');
        }
    }
    
    private function createTriggerForTable(string $table, string $triggerName, string $module): void
    {
        if (!$this->tableExists($table)) {
            echo "⚠️  Tabla no existe en módulo {$module}: {$table} (saltando...)\n";
            return;
        }
        
        DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
        
        DB::unprepared("
            CREATE TRIGGER {$triggerName}
            AFTER INSERT OR UPDATE OR DELETE ON {$table}
            FOR EACH ROW 
            EXECUTE FUNCTION audit_trigger_function();
        ");
        
        echo "✅ Trigger creado para tabla {$module}: {$table}\n";
    }
    
    private function dropUserPetTriggers(): void
    {
        $tables = [
            'users', 'usuarios', 'caracteristicas_usuarios', 'user_locations', 'usuario_contacto',
            'mascotas', 'caracteristicas_mascotas', 'bajas_mascotas',
            'ofertas_adopcion', 'solicitudes_adopcion',
        ];
        
        foreach ($tables as $table) {
            $triggerName = $table . '_audit_trigger';
            DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
            echo "✅ Trigger eliminado para tabla: {$table}\n";
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