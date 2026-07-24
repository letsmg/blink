<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

class ConsultationRoom extends Model
{
    protected $fillable = [
        'appointment_id',
        'room_name',
        'room_url',
        'moderator_token',
        'participant_token',
        'status',
        'started_at',
        'ended_at',
        'duration_minutes',
        'metadata',
        'encrypted_messages',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'json',
            // encrypted_messages é criptografado/descriptografado manualmente via Laravel encrypt/decrypt
        ];
    }

    /**
     * Appointment vinculado a esta sala de consulta.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Verifica se a sala está ativa (em andamento).
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se a sala já foi finalizada ou expirada.
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['ended', 'expired'], true);
    }

    /**
     * Finaliza a sala de consulta, calculando a duração real.
     */
    public function end(): void
    {
        $this->status = 'ended';
        $this->ended_at = now();

        if ($this->started_at) {
            $this->duration_minutes = (int) $this->started_at->diffInMinutes($this->ended_at);
        }

        $this->save();
    }

    /**
     * Criptografa e persiste o histórico de mensagens do chat.
     * Usa AES-256-GCM automaticamente via Laravel encrypt().
     */
    public function setEncryptedMessages(array $messages): void
    {
        $this->encrypted_messages = encrypt(json_encode($messages));
        $this->save();
    }

    /**
     * Descriptografa e retorna o histórico de mensagens do chat.
     */
    public function getDecryptedMessages(): array
    {
        if (empty($this->encrypted_messages)) {
            return [];
        }

        return json_decode(decrypt($this->encrypted_messages), true) ?? [];
    }
}