<?php

namespace App\Enums;

/**
 * UserRole enum - Rigid access control for the health system.
 * 
 * 0 => Patient (Paciente) - Can only access patient area
 * 1 => Admin (Staff - Administrador) - Full system access
 * 2 => Operational (Staff - Operacional) - Operational staff access
 */
enum UserRole: int
{
    case Patient = 0;
    case Admin = 1;
    case Operational = 2;

    /**
     * Check if the role belongs to the Staff group (Admin or Operational).
     */
    public function isStaff(): bool
    {
        return match ($this) {
            self::Admin, self::Operational => true,
            self::Patient => false,
        };
    }

    /**
     * Check if the role is Patient.
     */
    public function isPatient(): bool
    {
        return $this === self::Patient;
    }

    /**
     * Get the display label in Portuguese for the frontend.
     */
    public function label(): string
    {
        return match ($this) {
            self::Patient => 'Paciente',
            self::Admin => 'Administrador',
            self::Operational => 'Operacional',
        };
    }
}
