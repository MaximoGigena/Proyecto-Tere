<?php
// database/migrations/2025_12_29_999999_create_all_audit_triggers.php
// NOTA: Usa una fecha muy posterior (ej: 999999) para que sea la última migración

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar que la función existe, si no, crearla
        $this->createAuditFunctionIfNotExists();
        
        // Crear triggers para TODAS las tablas en una sola ejecución
        $this->createAllTriggers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar TODOS los triggers
        $this->dropAllTriggers();
        
        // Opcional: Eliminar la función (comenta si quieres mantenerla)
        // DB::unprepared('DROP FUNCTION IF EXISTS audit_trigger_function() CASCADE;');
    }
    
    /**
     * Crear la función de auditoría si no existe
     */
    private function createAuditFunctionIfNotExists(): void
    {
        // Primero verificar si la función ya existe
        $functionExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM pg_proc 
                WHERE proname = 'audit_trigger_function'
            ) as exists
        ");
        
        if (!$functionExists->exists) {
            DB::unprepared('
                CREATE OR REPLACE FUNCTION audit_trigger_function()
                RETURNS TRIGGER AS $$
                DECLARE
                    v_old_data JSONB;
                    v_new_data JSONB;
                    v_changed_columns TEXT[];
                    v_user_id BIGINT;
                    v_record_id BIGINT;
                BEGIN
                    -- Obtener user_id desde variables de sesión
                    BEGIN
                        v_user_id := NULLIF(current_setting(\'app.user_id\', TRUE), \'\')::BIGINT;
                    EXCEPTION WHEN OTHERS THEN
                        v_user_id := NULL;
                    END;
                    
                    IF (TG_OP = \'DELETE\') THEN
                        v_old_data = to_jsonb(OLD);
                        
                        -- Manejar diferentes nombres de columna ID
                        IF TG_TABLE_NAME = \'solicitudes_adopcion\' THEN
                            v_record_id := OLD."idSolicitud";
                        ELSIF TG_TABLE_NAME = \'ofertas_adopcion\' THEN
                            v_record_id := OLD."id_oferta";
                        ELSE
                            v_record_id := OLD.id;
                        END IF;
                        
                        INSERT INTO audit_logs (
                            table_name, record_id, action, old_data, new_data,
                            changed_columns, user_id, ip_address, user_agent, created_at
                        ) VALUES (
                            TG_TABLE_NAME, v_record_id, \'DELETE\', v_old_data, NULL,
                            NULL, v_user_id, 
                            NULLIF(current_setting(\'app.ip_address\', TRUE), \'\')::INET,
                            NULLIF(current_setting(\'app.user_agent\', TRUE), \'\'),
                            NOW()
                        );
                        
                        RETURN OLD;
                        
                    ELSIF (TG_OP = \'UPDATE\') THEN
                        v_old_data = to_jsonb(OLD);
                        v_new_data = to_jsonb(NEW);
                        
                        -- Manejar diferentes nombres de columna ID
                        IF TG_TABLE_NAME = \'solicitudes_adopcion\' THEN
                            v_record_id := NEW."idSolicitud";
                        ELSIF TG_TABLE_NAME = \'ofertas_adopcion\' THEN
                            v_record_id := NEW."id_oferta";
                        ELSE
                            v_record_id := NEW.id;
                        END IF;
                        
                        -- Encontrar columnas cambiadas
                        v_changed_columns := ARRAY[]::TEXT[];
                        
                        SELECT ARRAY_AGG(key)
                        INTO v_changed_columns
                        FROM (
                            SELECT key
                            FROM jsonb_each(v_old_data)
                            WHERE (v_old_data -> key) IS DISTINCT FROM (v_new_data -> key)
                            UNION
                            SELECT key
                            FROM jsonb_each(v_new_data)
                            WHERE (v_old_data -> key) IS DISTINCT FROM (v_new_data -> key)
                        ) AS diff;
                        
                        INSERT INTO audit_logs (
                            table_name, record_id, action, old_data, new_data,
                            changed_columns, user_id, ip_address, user_agent, created_at
                        ) VALUES (
                            TG_TABLE_NAME, v_record_id, \'UPDATE\', v_old_data, v_new_data,
                            COALESCE(v_changed_columns, ARRAY[]::TEXT[]), v_user_id,
                            NULLIF(current_setting(\'app.ip_address\', TRUE), \'\')::INET,
                            NULLIF(current_setting(\'app.user_agent\', TRUE), \'\'),
                            NOW()
                        );
                        
                        RETURN NEW;
                        
                    ELSIF (TG_OP = \'INSERT\') THEN
                        v_new_data = to_jsonb(NEW);
                        
                        -- Manejar diferentes nombres de columna ID
                        IF TG_TABLE_NAME = \'solicitudes_adopcion\' THEN
                            v_record_id := NEW."idSolicitud";
                        ELSIF TG_TABLE_NAME = \'ofertas_adopcion\' THEN
                            v_record_id := NEW."id_oferta";
                        ELSE
                            v_record_id := NEW.id;
                        END IF;
                        
                        INSERT INTO audit_logs (
                            table_name, record_id, action, old_data, new_data,
                            changed_columns, user_id, ip_address, user_agent, created_at
                        ) VALUES (
                            TG_TABLE_NAME, v_record_id, \'INSERT\', NULL, v_new_data,
                            NULL, v_user_id,
                            NULLIF(current_setting(\'app.ip_address\', TRUE), \'\')::INET,
                            NULLIF(current_setting(\'app.user_agent\', TRUE), \'\'),
                            NOW()
                        );
                        
                        RETURN NEW;
                    END IF;
                    
                    RETURN NULL;
                END;
                $$ LANGUAGE plpgsql;
            ');
            
            echo "✅ Función audit_trigger_function creada\n";
        } else {
            echo "✅ Función audit_trigger_function ya existe\n";
        }
    }
    
    /**
     * Crear todos los triggers
     */
    private function createAllTriggers(): void
    {
        $tables = [
            'users' => 'users_audit_trigger',
            'usuarios' => 'usuarios_audit_trigger',
            'caracteristicas_usuarios' => 'caracteristicas_usuarios_audit_trigger',
            'user_locations' => 'user_locations_audit_trigger',
            'usuario_contacto' => 'usuario_contacto_audit_trigger',
            'mascotas' => 'mascotas_audit_trigger',
            'caracteristicas_mascotas' => 'caracteristicas_mascotas_audit_trigger',
            'bajas_mascotas' => 'bajas_mascotas_audit_trigger',
            'ofertas_adopcion' => 'ofertas_adopcion_audit_trigger',
            'solicitudes_adopcion' => 'solicitudes_adopcion_audit_trigger',
        ];
        
        foreach ($tables as $table => $triggerName) {
            // Verificar si la tabla existe
            $tableExists = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = 'public' 
                    AND table_name = ?
                ) as exists
            ", [$table]);
            
            if ($tableExists->exists) {
                // Eliminar trigger si existe
                DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
                
                // Crear trigger
                DB::unprepared("
                    CREATE TRIGGER {$triggerName}
                    AFTER INSERT OR UPDATE OR DELETE ON {$table}
                    FOR EACH ROW 
                    EXECUTE FUNCTION audit_trigger_function();
                ");
                
                echo "✅ Trigger creado para tabla: {$table}\n";
            } else {
                echo "⚠️  Tabla no existe: {$table} (saltando...)\n";
            }
        }
    }
    
    /**
     * Eliminar todos los triggers
     */
    private function dropAllTriggers(): void
    {
        $tables = [
            'users' => 'users_audit_trigger',
            'usuarios' => 'usuarios_audit_trigger',
            'caracteristicas_usuarios' => 'caracteristicas_usuarios_audit_trigger',
            'user_locations' => 'user_locations_audit_trigger',
            'usuario_contacto' => 'usuario_contacto_audit_trigger',
            'mascotas' => 'mascotas_audit_trigger',
            'caracteristicas_mascotas' => 'caracteristicas_mascotas_audit_trigger',
            'bajas_mascotas' => 'bajas_mascotas_audit_trigger',
            'ofertas_adopcion' => 'ofertas_adopcion_audit_trigger',
            'solicitudes_adopcion' => 'solicitudes_adopcion_audit_trigger',
        ];
        
        foreach ($tables as $table => $triggerName) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
            echo "✅ Trigger eliminado para tabla: {$table}\n";
        }
    }
};