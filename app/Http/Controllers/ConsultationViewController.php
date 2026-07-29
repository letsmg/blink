<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ConsultationRoom;
use App\Services\JitsiVideoRoomService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

/**
 * Controller para renderizar a view Blade com o iframe do Jitsi Meet.
 *
 * A view recebe a URL de acesso com token JWT e embebe o iframe
 * para a experiência de teleatendimento E2E.
 */
class ConsultationViewController extends Controller
{
    protected JitsiVideoRoomService $jitsiService;

    public function __construct(JitsiVideoRoomService $jitsiService)
    {
        $this->jitsiService = $jitsiService;
    }

    /**
     * Renderiza a sala de teleatendimento para um appointment.
     *
     * Fluxo:
     * 1. Valida que o usuário tem acesso ao appointment
     * 2. Cria ou recupera a sala Jitsi existente
     * 3. Renderiza o iframe com a URL + JWT
     *
     * @param Request $request
     * @param int $appointmentId ID do agendamento
     * @return View
     */
    public function room(Request $request, int $appointmentId): View
    {
        $appointment = Appointment::with(['professional', 'patient.user', 'consultationRoom'])->findOrFail($appointmentId);

        // Valida que é teleatendimento
        if (!$appointment->isTelehealth()) {
            abort(400, 'Este agendamento não é de teleatendimento.');
        }

        // Verifica acesso: paciente vinculado, profissional vinculado, ou staff
        $user = $request->user();
        $isStaff = $user && $user->role?->isStaff();
        $isProfessional = $user && $appointment->professional->user_id === $user->id;
        $isPatient = $user && $appointment->patient->user_id === $user->id;

        if (!$isStaff && !$isProfessional && !$isPatient) {
            abort(403, 'Acesso não autorizado.');
        }

        // Obtém ou cria a sala
        $room = $appointment->consultationRoom;

        if (!$room) {
            // Cria a sala automaticamente se não existir
            $room = $this->jitsiService->createRoom(
                $appointment,
                $appointment->professional->full_name,
                $appointment->patient->full_name ?? 'Paciente'
            );
        }

        $isModerator = $isStaff || $isProfessional;
        $accessUrl = $this->jitsiService->getRoomAccessUrl($room, $isModerator);
        $displayName = $isModerator
            ? $appointment->professional->full_name
            : ($appointment->patient->full_name ?? 'Paciente');

        return view('consultation.room', [
            'accessUrl' => $accessUrl,
            'displayName' => $displayName,
            'isModerator' => $isModerator,
            'appointment' => $appointment,
            'room' => $room,
        ]);
    }
}