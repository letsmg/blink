<?php

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'agreement_id',
        'category',
        'is_active',
        'monthly_fee',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'monthly_fee' => 'decimal:2',
        ];
    }

    /**
     * Convênio ao qual este plano pertence.
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /**
     * Pacientes que possuem este plano de saúde.
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}