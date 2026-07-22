<?php

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

/**
 * Policy para autorização de ações em Appointments.
 * Isolamento de áreas: Patient só vê seus próprios agendamentos,
 * Staff (Admin + Operational) tem acesso total.
 */
class AppointmentPolicy
{
    /**
     * Determina se o usuário pode listar agendamentos.
     * Staff pode ver todos; Patient só pode ver seus próprios.
     */
    public function viewAny(User $user): bool
    {
        return $user->role instanceof UserRole;
    }

    /**
     * Determina se o usuário pode visualizar um agendamento específico.
     * Patient só pode ver agendamentos vinculados ao seu perfil.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->role->isStaff()) {
            return true;
        }

        return $user->patient && $appointment->patient_id === $user->patient->id;
    }

    /**
     * Determina se o usuário pode criar agendamentos.
     * Apenas Staff pode criar agendamentos diretamente.
     */
    public function create(User $user): bool
    {
        return $user->role->isStaff();
    }

    /**
     * Determina se o usuário pode atualizar um agendamento.
     * Apenas Staff pode atualizar.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->role->isStaff();
    }

    /**
     * Determina se o usuário pode deletar um agendamento.
     * Apenas Staff pode deletar (SoftDelete).
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->role->isStaff();
    }
}