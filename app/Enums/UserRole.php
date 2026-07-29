<?php

namespace App\Enums;

/**
 * UserRole enum - Rigid access control for the health system.
 * 
 * Slugs compatíveis com PostgreSQL RLS (Row Level Security):
 * - 'admin'   => Administrador Geral - Acesso total a todos os dados do sistema
 * - 'adminop' => Administrador Operacional - Acesso gerencial/operacional (agendas, cadastros), exceto configs globais
 * - 'prof'    => Profissional de Saúde - Acesso restrito aos seus próprios agendamentos, pacientes e horários
 * - 'patient' => Paciente - Acesso restrito exclusivamente aos seus próprios agendamentos e dados cadastrais
 */
enum UserRole: string
{
    case Admin = 'admin';
    case AdminOperational = 'adminop';
    case Professional = 'prof';
    case Patient = 'patient';

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