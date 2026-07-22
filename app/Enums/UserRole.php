<?php

namespace App\Enums;

/**
 * UserRole enum - Rigid access control for the health system.
 * 
 * 1 => Admin (Administrador Geral) - Visualiza todos os dados, bloqueia sistema/profissionais
 * 2 => AdminOperational (Administrador Operacional) - Não pode gerenciar outros admins
 * 3 => Professional (Profissional de Saúde) - Gerencia agenda, diagnósticos e histórico de seus pacientes
 * 4 => Patient (Paciente) - Login próprio, gerencia seus dados, consultas e visualiza diagnósticos
 */
enum UserRole: int
{
    case Admin = 1;
    case AdminOperational = 2;
    case Professional = 3;
    case Patient = 4;

    /**
     * Check if the role belongs to the Staff group (Admin, AdminOperational or Professional).
     */
    public function isStaff(): bool
    {
        return match ($this) {
            self::Admin, self::AdminOperational, self::Professional => true,
            self::Patient => false,
        };
    }

    /**
     * Check if the role has administrative privileges.
     */
    public function isAdmin(): bool
    {
        return match ($this) {
            self::Admin, self::AdminOperational => true,
            default => false,
        };
    }

    /**
     * Check if the role is the Super Admin (Admin Geral).
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Check if the role is a Professional.
     */
    public function isProfessional(): bool
    {
        return $this === self::Professional;
    }

    /**
     * Check if the role is a Patient.
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
            self::Admin => 'Administrador Geral',
            self::AdminOperational => 'Administrador Operacional',
            self::Professional => 'Profissional de Saúde',
            self::Patient => 'Paciente',
        };
    }
}