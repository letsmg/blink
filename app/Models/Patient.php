<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'cpf_encrypted',
        'cpf_hash',
        'main_complaint',
        'street',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'clinical_history',
        'phone1',
        'phone2',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['cpf_masked', 'birth_date'];

    /**
     * Get the masked CPF (show only last 3 digits).
     */
    public function getCpfMaskedAttribute(): ?string
    {
        return $this->cpf_hash
            ? '***.***.***-'.substr($this->cpf_hash, -3)
            : null;
    }

    /**
     * Alias for date_of_birth for frontend compatibility.
     */
    public function getBirthDateAttribute(): ?string
    {
        return $this->date_of_birth;
    }

    /**
     * User account relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Appointments for this patient.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Diagnostics for this patient.
     */
    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }
}
