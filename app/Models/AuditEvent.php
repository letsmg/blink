<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro imutável de auditoria para todo CRUD do sistema.
 * 
 * Intenção: Rastrear quem fez o quê, quando e de onde,
 * com snapshots dos dados antes e depois de cada operação.
 */
class AuditEvent extends Model
{
    // Audit events are immutable — only created_at, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Usuário que executou a ação.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}