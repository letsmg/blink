<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de salas de consulta para teleatendimento.
     * Armazena o vínculo com o appointment, token da sala Jitsi e logs de auditoria.
     */
    public function up(): void
    {
        Schema::create('consultation_rooms', function (Blueprint $table) {
            $table->id();

            // Vínculo 1:1 com appointment (cada consulta tem no máximo uma sala)
            $table->foreignId('appointment_id')
                  ->unique()
                  ->constrained('appointments')
                  ->onDelete('cascade');

            // Dados da sala Jitsi
            $table->string('room_name')->unique()->comment('Nome único da sala no Jitsi (ex: consulta-{appointment_id}-{uuid})');
            $table->text('room_url')->nullable()->comment('URL completa da sala Jitsi');

            // Token JWT para autenticação no Jitsi (expira após a consulta)
            $table->text('moderator_token')->nullable()->comment('Token JWT para o profissional (moderador)');
            $table->text('participant_token')->nullable()->comment('Token JWT para o paciente (participante)');

            // Status da sala
            $table->enum('status', ['created', 'active', 'ended', 'expired'])
                  ->default('created')
                  ->comment('created=sala criada, active=em andamento, ended=finalizada, expired=expirada');

            // Datas da sessão
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Duração real calculada da consulta');

            // Logs de auditoria e segurança
            $table->json('metadata')->nullable()->comment('IPs, user-agents, eventos de entrada/saída');

            // Histórico de chat criptografado em repouso (AES-256-GCM via Laravel encrypt)
            $table->text('encrypted_messages')->nullable()->comment('Histórico de chat criptografado em repouso');

            $table->timestamps();

            // Índices para performance
            $table->index('room_name');
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_rooms');
    }
};