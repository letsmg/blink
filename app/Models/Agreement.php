<?php

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agreement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'coverage_percentage',
        'consultation_fee',
        'is_active',
        'start_date',
        'end_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'coverage_percentage' => 'decimal:2',
            'consultation_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Empresa conveniada dona deste convênio.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Profissionais autorizados a atender por este convênio.
     * Tabela pivô: agreement_professional com campo custom_fee.
     */
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'agreement_professional')
            ->withPivot('custom_fee')
            ->withTimestamps();
    }

    /**
     * Planos de saúde vinculados a este convênio.
     */
    public function healthPlans(): HasMany
    {
        return $this->hasMany(HealthPlan::class);
    }
}