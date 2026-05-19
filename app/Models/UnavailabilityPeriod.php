<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Períodos em que um profissional não estará disponível para atendimento.
 * Usado para bloquear datas no calendário de agendamentos.
 */
class UnavailabilityPeriod extends Model
{
    protected $fillable = [
        'professional_id',
        'start_date',
        'end_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Professional who is unavailable during this period.
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }
}
