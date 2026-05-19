<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diagnostic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'appointment_id',
        'diagnosis_date',
        'description',
        'prescription',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'diagnosis_date' => 'date',
        ];
    }

    /**
     * Patient who received this diagnosis.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Professional who made this diagnosis.
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Appointment associated with this diagnosis.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
