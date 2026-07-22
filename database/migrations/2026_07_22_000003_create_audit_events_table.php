<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de Auditoria — registra todo CRUD em todas as tabelas do sistema.
     * Cada operação de criação, atualização ou exclusão em qualquer entidade
     * gera um registro imutável com o usuário responsável, tipo de operação,
     * dados antes/depois (diffs) e metadados de contexto (IP, user agent).
     */
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // created, updated, deleted, restored, blocked, unblocked, login, logout
            $table->string('auditable_type'); // Nome da classe/entidade afetada (ex: App\Models\Patient)
            $table->unsignedBigInteger('auditable_id'); // ID do registro afetado
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Quem executou
            $table->json('old_values')->nullable(); // Valores antes da alteração (dados sensíveis mascarados)
            $table->json('new_values')->nullable(); // Valores após a alteração (dados sensíveis mascarados)
            $table->string('ip_address', 45)->nullable(); // IP de origem
            $table->text('user_agent')->nullable(); // User-Agent do navegador
            $table->text('metadata')->nullable(); // Dados extras de contexto em JSON
            $table->timestamp('created_at')->useCurrent(); // Apenas created_at (registros são imutáveis)

            // Índices para consultas de auditoria
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};