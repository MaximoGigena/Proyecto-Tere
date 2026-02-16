<?php
// database/migrations/2026_01_23_999999_create_all_audit_triggers.php
// Migración unificada que reemplaza todas las anteriores

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear la función de auditoría (si no existe)
        $this->createAuditFunctionIfNotExists();
        
        // Crear triggers para TODAS las tablas
        $this->createAllTriggers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar TODOS los triggers
        $this->dropAllTriggers();
        
        // Opcional: Comenta la siguiente línea si quieres mantener la función
        // $this->dropAuditFunction();
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
     * Crear todos los triggers para todas las tablas
     */
    private function createAllTriggers(): void
    {
        // Lista completa de todas las tablas que necesitan auditoría
        $allTables = [
            // Tablas de usuarios
            'users' => 'users_audit_trigger',
            'usuarios' => 'usuarios_audit_trigger',
            'caracteristicas_usuarios' => 'caracteristicas_usuarios_audit_trigger',
            'user_locations' => 'user_locations_audit_trigger',
            'usuario_contacto' => 'usuario_contacto_audit_trigger',
            
            // Tablas de mascotas
            'mascotas' => 'mascotas_audit_trigger',
            'caracteristicas_mascotas' => 'caracteristicas_mascotas_audit_trigger',
            'bajas_mascotas' => 'bajas_mascotas_audit_trigger',
            
            // Tablas de adopción
            'ofertas_adopcion' => 'ofertas_adopcion_audit_trigger',
            'solicitudes_adopcion' => 'solicitudes_adopcion_audit_trigger',
            
            // Tablas de procedimientos médicos
            'alergias' => 'alergias_audit_trigger',
            'cirugias' => 'cirugias_audit_trigger',
            'cuidados_paliativos' => 'cuidados_paliativos_audit_trigger',
            'desparasitaciones' => 'desparasitaciones_audit_trigger',
            'diagnosticos' => 'diagnosticos_audit_trigger',
            'farmacos' => 'farmacos_audit_trigger',
            'revisiones' => 'revisiones_audit_trigger',
            'terapias' => 'terapias_audit_trigger',
            'vacunas' => 'vacunas_audit_trigger',
            
            // Tablas de tipos (categorías)
            'tipos_alergia' => 'tipos_alergia_audit_trigger',
            'tipos_cirugia' => 'tipos_cirugia_audit_trigger',
            'tipos_paliativo' => 'tipos_paliativo_audit_trigger',
            'tipos_desparasitacion' => 'tipos_desparasitacion_audit_trigger',
            'tipos_diagnostico' => 'tipos_diagnostico_audit_trigger',
            'tipos_farmaco' => 'tipos_farmaco_audit_trigger',
            'tipos_revision' => 'tipos_revision_audit_trigger',
            'tipos_terapia' => 'tipos_terapia_audit_trigger',
            'tipos_vacuna' => 'tipos_vacuna_audit_trigger',
            
            // Tablas de relación/intermedia
            'procesos_medicos' => 'procesos_medicos_audit_trigger',
            'procedimiento_diagnosticos' => 'procedimiento_diagnosticos_audit_trigger',
            'archivo_cirugias' => 'archivo_cirugias_audit_trigger',
            'archivos_terapia' => 'archivos_terapia_audit_trigger',
            'farmacos_asociados' => 'farmacos_asociados_audit_trigger',
            'revision_diagnosticos' => 'revision_diagnosticos_audit_trigger',
            
            // Tablas de historial
            'procedimiento_diagnostico_historial' => 'procedimiento_diagnostico_historial_audit_trigger',
            
            // Tablas de veterinarios
            'veterinarios' => 'veterinarios_audit_trigger',
            'solicitudes_veterinarios' => 'solicitudes_veterinarios_audit_trigger',
            'centros_veterinarios' => 'centros_veterinarios_audit_trigger',
            'caracteristicas_veterinarios' => 'caracteristicas_veterinarios_audit_trigger',
            'contacto_veterinarios' => 'contacto_veterinarios_audit_trigger',
            
            // Nota: Las tablas de tipos ya están incluidas arriba, no es necesario duplicarlas
        ];
        
        foreach ($allTables as $table => $triggerName) {
            $this->createTriggerForTable($table, $triggerName);
        }
        
        echo "\n🎯 Todos los triggers han sido creados/actualizados\n";
    }
    
    /**
     * Crear trigger para una tabla específica
     */
    private function createTriggerForTable(string $table, string $triggerName): void
    {
        // Verificar si la tabla existe
        $tableExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            ) as exists
        ", [$table]);
        
        if (!$tableExists->exists) {
            echo "⚠️  Tabla no existe: {$table} (saltando...)\n";
            return;
        }
        
        // Verificar si la tabla tiene columna 'id' o manejamos caso especial
        $hasIdColumn = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_schema = 'public' 
                AND table_name = ? 
                AND column_name IN ('id', 'idSolicitud', 'id_oferta')
                LIMIT 1
            ) as exists
        ", [$table]);
        
        if (!$hasIdColumn->exists) {
            echo "⚠️  Tabla {$table} no tiene columna ID reconocida (saltando...)\n";
            return;
        }
        
        // Eliminar trigger si ya existe
        DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
        
        // Crear nuevo trigger
        DB::unprepared("
            CREATE TRIGGER {$triggerName}
            AFTER INSERT OR UPDATE OR DELETE ON {$table}
            FOR EACH ROW 
            EXECUTE FUNCTION audit_trigger_function();
        ");
        
        echo "✅ Trigger creado/actualizado para tabla: {$table}\n";
    }
    
    /**
     * Eliminar todos los triggers
     */
    private function dropAllTriggers(): void
    {
        $allTables = [
            // Tablas de usuarios
            'users', 'usuarios', 'caracteristicas_usuarios', 'user_locations', 'usuario_contacto',
            
            // Tablas de mascotas
            'mascotas', 'caracteristicas_mascotas', 'bajas_mascotas',
            
            // Tablas de adopción
            'ofertas_adopcion', 'solicitudes_adopcion',
            
            // Tablas de procedimientos médicos
            'alergias', 'cirugias', 'cuidados_paliativos', 'desparasitaciones', 'diagnosticos',
            'farmacos', 'revisiones', 'terapias', 'vacunas',
            
            // Tablas de tipos
            'tipos_alergia', 'tipos_cirugia', 'tipos_paliativo', 'tipos_desparasitacion',
            'tipos_diagnostico', 'tipos_farmaco', 'tipos_revision', 'tipos_terapia', 'tipos_vacuna',
            
            // Tablas de relación
            'procesos_medicos', 'procedimiento_diagnosticos', 'archivo_cirugias',
            'archivos_terapia', 'farmacos_asociados', 'revision_diagnosticos',
            
            // Tablas de historial
            'procedimiento_diagnostico_historial',
            
            // Tablas de veterinarios
            'veterinarios', 'solicitudes_veterinarios', 'centros_veterinarios',
            'caracteristicas_veterinarios', 'contacto_veterinarios',
        ];
        
        foreach ($allTables as $table) {
            $triggerName = $table . '_audit_trigger';
            DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName} ON {$table} CASCADE;");
            echo "✅ Trigger eliminado para tabla: {$table}\n";
        }
        
        echo "\n🗑️  Todos los triggers han sido eliminados\n";
    }
    
    /**
     * Eliminar la función de auditoría (opcional)
     */
    private function dropAuditFunction(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS audit_trigger_function() CASCADE;');
        echo "🗑️  Función audit_trigger_function eliminada\n";
    }
};