<?php
// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates users table with role enum, terms acceptance, Argon2id password hashing,
     * and PII parity structure (first_name/last_name encrypted + display_name plain text).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Nome exibível seguro para listagens (apenas primeiro nome ou apelido)
            $table->string('display_name');
            // Paridade PII: first_name_hash + first_name_encrypted
            $table->string('first_name_hash')->nullable(); // SHA-256 para busca
            $table->text('first_name_encrypted')->nullable(); // AES-256 para descriptografia em memória
            // Paridade PII: last_name_hash + last_name_encrypted
            $table->string('last_name_hash')->nullable();
            $table->text('last_name_encrypted')->nullable();
            // Email mantido em texto puro apenas para login (exceção operacional)
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // Argon2id hashed via Laravel's 'hashed' cast
            $table->string('role')->default(UserRole::Patient->value);
            // Bloqueio de conta — Admin Geral pode bloquear/desbloquear usuários e profissionais
            $table->boolean('is_blocked')->default(false);
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version', 20)->nullable();

            $table->rememberToken();
            $table->timestamps();

            // Índices para busca por hash de nome
            $table->index('first_name_hash');
            $table->index('last_name_hash');
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