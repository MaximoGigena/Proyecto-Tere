<?php
// database/migrations/2026_01_24_000002_create_audit_function.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('
            CREATE OR REPLACE FUNCTION audit_trigger_function()
            RETURNS TRIGGER AS $$
            DECLARE
                v_old_data JSONB;
                v_new_data JSONB;
                v_changed_columns TEXT[];
                v_user_id BIGINT;
                v_record_id BIGINT;
                v_id_field TEXT;
            BEGIN
                -- Obtener user_id desde variables de sesión
                BEGIN
                    v_user_id := NULLIF(current_setting(\'app.user_id\', TRUE), \'\')::BIGINT;
                EXCEPTION 
                    WHEN OTHERS THEN
                        v_user_id := NULL;
                END;
                
                -- Determinar el campo ID basado en el nombre de la tabla
                CASE TG_TABLE_NAME
                    -- Tablas con ID especial
                    WHEN \'solicitudes_adopcion\' THEN
                        v_id_field := \'idSolicitud\';
                    WHEN \'ofertas_adopcion\' THEN
                        v_id_field := \'id_oferta\';
                    WHEN \'procedimiento_diagnostico_historial\' THEN
                        v_id_field := \'historial_id\';
                    -- Para todas las demás tablas, usar \'id\' por defecto
                    ELSE
                        v_id_field := \'id\';
                END CASE;
                
                -- Manejar DELETE
                IF (TG_OP = \'DELETE\') THEN
                    v_old_data = to_jsonb(OLD);
                    
                    -- Obtener el ID del registro
                    EXECUTE format(\'SELECT ($1).%I\', v_id_field) INTO v_record_id USING OLD;
                    
                    -- Respaldo si no se puede obtener el ID
                    IF v_record_id IS NULL THEN
                        BEGIN
                            v_record_id := OLD.id;
                        EXCEPTION WHEN OTHERS THEN
                            v_record_id := 0;
                        END;
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
                    
                -- Manejar UPDATE
                ELSIF (TG_OP = \'UPDATE\') THEN
                    v_old_data = to_jsonb(OLD);
                    v_new_data = to_jsonb(NEW);
                    
                    -- Obtener el ID del registro
                    EXECUTE format(\'SELECT ($1).%I\', v_id_field) INTO v_record_id USING NEW;
                    
                    -- Respaldo si no se puede obtener el ID
                    IF v_record_id IS NULL THEN
                        BEGIN
                            v_record_id := NEW.id;
                        EXCEPTION WHEN OTHERS THEN
                            v_record_id := 0;
                        END;
                    END IF;
                    
                    -- Encontrar columnas cambiadas
                    v_changed_columns := ARRAY[]::TEXT[];
                    
                    WITH differences AS (
                        SELECT key
                        FROM jsonb_each(v_old_data)
                        WHERE (v_old_data -> key) IS DISTINCT FROM (v_new_data -> key)
                        UNION
                        SELECT key
                        FROM jsonb_each(v_new_data)
                        WHERE (v_old_data -> key) IS DISTINCT FROM (v_new_data -> key)
                    )
                    SELECT ARRAY_AGG(key)
                    INTO v_changed_columns
                    FROM differences;
                    
                    INSERT INTO audit_logs (
                        table_name, record_id, action, old_data, new_data,
                        changed_columns, user_id, ip_address, user_agent, created_at
                    ) VALUES (
                        TG_TABLE_NAME, v_record_id, \'UPDATE\', v_old_data, v_new_data,
                        COALESCE(to_jsonb(v_changed_columns), \'[]\'::jsonb), v_user_id,
                        NULLIF(current_setting(\'app.ip_address\', TRUE), \'\')::INET,
                        NULLIF(current_setting(\'app.user_agent\', TRUE), \'\'),
                        NOW()
                    );
                    
                    RETURN NEW;
                    
                -- Manejar INSERT
                ELSIF (TG_OP = \'INSERT\') THEN
                    v_new_data = to_jsonb(NEW);
                    
                    -- Obtener el ID del registro
                    EXECUTE format(\'SELECT ($1).%I\', v_id_field) INTO v_record_id USING NEW;
                    
                    -- Respaldo si no se puede obtener el ID
                    IF v_record_id IS NULL THEN
                        BEGIN
                            v_record_id := NEW.id;
                        EXCEPTION WHEN OTHERS THEN
                            v_record_id := 0;
                        END;
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
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS audit_trigger_function() CASCADE;');
    }
};