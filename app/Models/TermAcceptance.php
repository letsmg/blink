<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para registrar o aceite dos termos de uso e política de privacidade.
 *
 * Persiste no banco de dados o IP, geolocalização e user-agent do visitante
 * no momento do aceite, garantindo conformidade com a LGPD.
 * O aceite NÃO fica salvo apenas em cookies ou sessão.
 */
class TermAcceptance extends Model
{
    protected $fillable = [
        'user_id',
        'term_type',
        'ip_address',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'user_agent',
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
     * Relacionamento com o usuário (nullable para visitantes não logados).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
