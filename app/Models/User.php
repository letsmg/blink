<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'display_name',
        'first_name_hash',
        'first_name_encrypted',
        'last_name_hash',
        'last_name_encrypted',
        'email',
        'password',
        'role',
        'is_blocked',
        'blocked_by',
        'blocked_at',
        'terms_accepted',
        'terms_accepted_at',
        'terms_version',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Argon2id via Laravel hashing
            'role' => UserRole::class,
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'terms_accepted' => 'boolean',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /**
     * Patient profile relationship (only for Patient role).
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Professional profile relationship (only for Staff roles).
     */
    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    /**
     * Check if user has accepted the terms of use.
     */
    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted && $this->terms_accepted_at !== null;
    }

    /**
     * Check if user belongs to Staff group.
     */
    public function isStaff(): bool
    {
        return $this->role instanceof UserRole && $this->role->isStaff();
    }

    /**
     * Check if user is a Patient.
     */
    public function isPatient(): bool
    {
        return $this->role instanceof UserRole && $this->role->isPatient();
    }

    /**
     * Check if the user's account is blocked.
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    /**
     * Block this user. Only Admin Geral can perform this action.
     */
    public function block(User $blockedBy): void
    {
        $this->update([
            'is_blocked' => true,
            'blocked_by' => $blockedBy->id,
            'blocked_at' => now(),
        ]);
    }

    /**
     * Unblock this user.
     */
    public function unblock(): void
    {
        $this->update([
            'is_blocked' => false,
            'blocked_by' => null,
            'blocked_at' => null,
        ]);
    }
}