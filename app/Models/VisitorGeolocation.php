<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model para histórico de IPs e geolocalização de visitantes (não logados).
 *
 * Mantém um registro independente para cada visita/aceite de termos,
 * garantindo conformidade com a LGPD.
 * Histórico separado de usuários logados (UserGeolocation).
 */
class VisitorGeolocation extends Model
{
    protected $fillable = [
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
}
