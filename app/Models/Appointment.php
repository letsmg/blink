<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'appointment_date',
        'notes',
        'is_paid',
        'payment_method',
        'paid_at',
        'is_return',
        'original_appointment_id',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'is_return' => 'boolean',
        ];
    }

    /**
     * Patient who owns this appointment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Professional responsible for this appointment.
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Original appointment if this is a return visit.
     */
    public function originalAppointment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_appointment_id');
    }

    /**
     * Return appointments linked to this one.
     */
    public function returnAppointments()
    {
        return $this->hasMany(self::class, 'original_appointment_id');
    }
}
