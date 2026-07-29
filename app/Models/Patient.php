<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'cpf_encrypted',
        'cpf_hash',
        'main_complaint',
        'street_hash',
        'street_encrypted',
        'neighborhood_hash',
        'neighborhood_encrypted',
        'city_hash',
        'city_encrypted',
        'state',
        'zip_code',
        'clinical_history',
        'phone1',
        'phone2',
        'companion_first_name_encrypted',
        'companion_phone_encrypted',
        'health_plan_id',
    ];

    protected $appends = ['cpf_masked', 'birth_date', 'full_name'];

    /**
     * Masked CPF — mostra apenas os últimos 3 dígitos do hash.
     */
    public function getCpfMaskedAttribute(): ?string
    {
        return $this->cpf_hash
            ? '***.***.***-'.substr($this->cpf_hash, -3)
            : null;
    }

    public function getBirthDateAttribute(): ?string
    {
        return $this->date_of_birth;
    }

    /**
     * Nome completo delegado ao relacionamento com users.display_name.
     * Mantém compatibilidade com código legado que referencia patient->full_name.
     */
    public function getFullNameAttribute(): ?string
    {
        // Se o relacionamento user já foi carregado, usa diretamente
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->display_name;
        }

        // Caso contrário, busca via query para evitar N+1 em coleções
        return $this->user()->value('display_name');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class);
    }
}