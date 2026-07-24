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
        'location_id',
        'type',
        'clinic_id',
        'date',
        'time',
        'notes',
        'is_paid',
        'payment_method',
        'paid_at',
        'amount',
        'is_return',
        'original_appointment_id',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'patient_notes',
        'professional_notes',
        'started_at',
        'ended_at',
        'agreement_id',
        'health_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'string',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'is_return' => 'boolean',
            'cancelled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
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
     * Location where this appointment takes place.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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

    /**
     * Convênio utilizado neste agendamento.
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /**
     * Plano de saúde utilizado neste agendamento.
     */
    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class);
    }

    /**
     * Clínica onde ocorrerá o atendimento presencial.
     * Nulo para teleatendimento.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'clinic_id');
    }

    /**
     * Sala de teleatendimento vinculada a esta consulta (1:1).
     */
    public function consultationRoom()
    {
        return $this->hasOne(ConsultationRoom::class);
    }

    /**
     * Verifica se o agendamento é do tipo teleatendimento.
     */
    public function isTelehealth(): bool
    {
        return $this->type === 'telehealth';
    }

    /**
     * Verifica se o agendamento é do tipo presencial.
     */
    public function isPresencial(): bool
    {
        return $this->type === 'presencial';
    }
}
