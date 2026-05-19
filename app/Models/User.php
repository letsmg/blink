<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'terms_accepted', 'terms_accepted_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Argon2id via Laravel hashing
            'role' => UserRole::class, // Auto-cast to UserRole enum
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
}
