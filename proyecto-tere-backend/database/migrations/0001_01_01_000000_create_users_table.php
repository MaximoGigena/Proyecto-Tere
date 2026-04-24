<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('userable_id');
            $table->string('userable_type');
            $table->rememberToken();
            $table->timestamps();
            $table->enum('estado', ['pendiente', 'activo', 'inactivo', 'suspendido', 'bloqueado'])->default('pendiente');
            $table->index(['userable_type', 'userable_id']);

            // ✅ Campos de Telegram
            $table->string('telegram_chat_id')->nullable()->after('estado');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->string('telegram_first_name')->nullable()->after('telegram_username');
            $table->string('telegram_last_name')->nullable()->after('telegram_first_name');
            $table->timestamp('telegram_verified_at')->nullable()->after('telegram_last_name');
            $table->string('telegram_token')->nullable()->after('telegram_verified_at');
            $table->timestamp('telegram_token_expires_at')->nullable()->after('telegram_token');

            // Campos de autenticación social
            $table->string('google_id')->nullable()->unique();
            $table->string('facebook_id')->nullable()->unique();
            $table->string('avatar')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};