<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Service para registro de auditoria de todo CRUD do sistema.
 * 
 * Intenção: Centralizar a criação de AuditEvent com sanitização automática
 * de dados sensíveis (PII) antes de persistir os snapshots.
 */
class AuditService
{
    /**
     * Campos sensíveis que NUNCA devem aparecer nos snapshots de auditoria.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'cpf_encrypted',
        'cpf_hash',
        'first_name_encrypted',
        'first_name_hash',
        'last_name_encrypted',
        'last_name_hash',
        'cnpj_encrypted',
        'cnpj_hash',
        'street_encrypted',
        'neighborhood_encrypted',
        'city_encrypted',
        'companion_first_name_encrypted',
        'companion_phone_encrypted',
    ];

    /**
     * Registra um evento de auditoria.
     *
     * @param string $eventType created, updated, deleted, restored, blocked, unblocked, login, logout
     * @param Model $auditable O modelo afetado pela operação
     * @param User|null $user Usuário que executou a ação
     * @param array|null $oldValues Valores antes da alteração
     * @param array|null $newValues Valores após a alteração
     * @param Request|null $request Request para extrair IP e User-Agent
     */
    public function log(
        string $eventType,
        Model $auditable,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
    ): AuditEvent {
        return AuditEvent::create([
            'event_type'     => $eventType,
            'auditable_type' => get_class($auditable),
            'auditable_id'   => $auditable->getKey(),
            'user_id'        => $user?->id,
            'old_values'     => $oldValues ? $this->sanitize($oldValues) : null,
            'new_values'     => $newValues ? $this->sanitize($newValues) : null,
            'ip_address'     => $request?->ip(),
            'user_agent'     => $request?->userAgent(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Remove campos sensíveis do array de dados para não vazar PII nos snapshots.
     */
    private function sanitize(array $data): array
    {
        return array_filter(
            $data,
            fn (string $key) => ! in_array($key, self::SENSITIVE_FIELDS, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}