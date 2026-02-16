<?php
// database/migrations/2026_01_24_000001_create_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['INSERT', 'UPDATE', 'DELETE']);
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->jsonb('changed_columns')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Índices básicos
            $table->index(['table_name', 'record_id'], 'idx_audit_table_record');
            $table->index('user_id', 'idx_audit_user_id');
            $table->index('action', 'idx_audit_action');
            $table->index('created_at', 'idx_audit_created_at');
            
            // Índices compuestos para consultas comunes
            $table->index(['table_name', 'action'], 'idx_audit_table_action');
            $table->index(['table_name', 'created_at'], 'idx_audit_table_created');
            $table->index(['user_id', 'created_at'], 'idx_audit_user_created');
            
            // Índices GIN para JSONB
            $table->index('old_data', 'idx_audit_old_data_gin')->algorithm('gin');
            $table->index('new_data', 'idx_audit_new_data_gin')->algorithm('gin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};