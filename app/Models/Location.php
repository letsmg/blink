<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'zip_code',
        'neighborhood',
        'city',
    ];

    /**
     * Professionals who provide care at this location.
     * Many-to-Many relationship via location_professional pivot table.
     */
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'location_professional')
            ->withTimestamps();
    }

    /**
     * Appointments scheduled at this location.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
