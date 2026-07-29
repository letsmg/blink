<?php

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

/**
 * Policy para autorização de ações em Appointments.
 * 
 * Regras de visibilidade pós-refatoração (slugs):
 * - 'admin' + 'adminop': acesso total (CRUD em todos os agendamentos)
 * - 'prof': acesso restrito aos seus próprios agendamentos (via professional_id)
 * - 'patient': acesso restrito exclusivamente aos seus próprios agendamentos (via patient_id)
 */
class AppointmentPolicy
{
    /**
     * Determina se o usuário pode listar agendamentos.
     * Admin/AdminOp veem todos; Prof vê apenas os seus; Patient vê apenas os seus.
     */
    public function viewAny(User $user): bool
    {
        return $user->role instanceof UserRole;
    }

    /**
     * Determina se o usuário pode visualizar um agendamento específico.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Admin e AdminOperational têm acesso total
        if ($user->role->isAdmin()) {
            return true;
        }

        // Professional vê apenas agendamentos vinculados a ele
        if ($user->role->isProfessional()) {
            return $user->professional && $appointment->professional_id === $user->professional->id;
        }

        // Patient vê apenas agendamentos vinculados ao seu perfil
        return $user->patient && $appointment->patient_id === $user->patient->id;
    }

    /**
     * Determina se o usuário pode criar agendamentos.
     * Admin e AdminOperational podem criar diretamente. Professional pode criar
     * apenas agendamentos vinculados a si mesmo.
     */
    public function create(User $user): bool
    {
        return $user->role->isAdmin() || $user->role->isProfessional();
    }

    /**
     * Determina se o usuário pode atualizar um agendamento.
     * Admin/AdminOp: total. Professional: apenas os seus.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->role->isAdmin()) {
            return true;
        }

        if ($user->role->isProfessional()) {
            return $user->professional && $appointment->professional_id === $user->professional->id;
        }

        return false;
    }

    /**
     * Determina se o usuário pode deletar um agendamento.
     * Admin/AdminOp: total. Professional: apenas os seus.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->role->isAdmin()) {
            return true;
        }

        if ($user->role->isProfessional()) {
            return $user->professional && $appointment->professional_id === $user->professional->id;
        }

        return false;
    }
}