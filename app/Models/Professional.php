<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'specialty',
        'professional_document',
        'phone',
        'is_active',
    ];

    /**
     * User account relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Appointments for this professional.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Diagnostics made by this professional.
     */
    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    /**
     * Unavailability periods for this professional.
     * Used to block dates on the appointment calendar.
     */
    public function unavailabilityPeriods(): HasMany
    {
        return $this->hasMany(UnavailabilityPeriod::class);
    }
}
