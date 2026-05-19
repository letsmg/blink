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
    ];

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
