<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para histórico de IPs e geolocalização de usuários logados.
 *
 * Mantém um registro independente para cada login/aceite de termos,
 * garantindo conformidade com a LGPD e auditoria de segurança.
 * Histórico separado de visitantes (VisitorGeolocation).
 */
class UserGeolocation extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'user_agent',
        'term_type',
        'terms_version',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Relacionamento com o usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
