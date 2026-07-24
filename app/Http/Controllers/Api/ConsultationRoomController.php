<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationRoom;
use App\Services\JitsiVideoRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

/**
 * Controller responsável pelo gerenciamento de salas de teleatendimento.
 *
 * Gerencia criação, acesso, início e finalização de salas Jitsi Meet
 * vinculadas a agendamentos do tipo 'telehealth'.
 */
class ConsultationRoomController extends Controller
{
    protected JitsiVideoRoomService $jitsiService;

    public function __construct(JitsiVideoRoomService $jitsiService)
    {
        $this->jitsiService = $jitsiService;
    }

    /**
     * Cria uma nova sala de teleatendimento para um appointment.
     *
     * Regras de negócio:
     * - Apenas o profissional vinculado ou staff pode criar a sala
     * - O appointment deve ser do tipo 'telehealth'
     * - Se a sala já existir, retorna a existente (idempotência)
     */
    public function store(Request $request, Appointment $appointment): JsonResponse
    {
        // Valida que é teleatendimento
        if (!$appointment->isTelehealth()) {
            return response()->json([
                'message' => 'Apenas agendamentos de teleatendimento podem ter sala virtual.',
            ], 422);
        }

        // Verifica se o usuário autenticado é o profissional ou staff (Admin/AdminOperational)
        $user = $request->user();
        $isStaff = $user->role?->isStaff();
        $isProfessional = $appointment->professional->user_id === $user->id;

        if (!$isStaff && !$isProfessional) {
            return response()->json([
                'message' => 'Apenas o profissional ou staff pode criar a sala de consulta.',
            ], 403);
        }

        // Cria a sala (idempotente)
        try {
            $room = $this->jitsiService->createRoom(
                $appointment,
                $appointment->professional->full_name,
                $appointment->patient->full_name ?? 'Paciente'
            );

            return response()->json([
                'message' => 'Sala de consulta criada com sucesso.',
                'data' => [
                    'room' => $room,
                    'moderator_url' => $this->jitsiService->getRoomAccessUrl($room, true),
                    'participant_url' => $this->jitsiService->getRoomAccessUrl($room, false),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar sala de consulta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna os dados da sala de consulta e URLs de acesso.
     *
     * Acesso: profissional, paciente vinculado, ou staff.
     */
    public function show(Request $request, ConsultationRoom $room): JsonResponse
    {
        $user = $request->user();
        $appointment = $room->appointment;

        // Verifica permissão de acesso usando o enum UserRole
        $isStaff = $user->role?->isStaff();
        $isProfessional = $appointment->professional->user_id === $user->id;
        $isPatient = $appointment->patient->user_id === $user->id;

        if (!$isStaff && !$isProfessional && !$isPatient) {
            return response()->json(['message' => 'Acesso não autorizado a esta sala.'], 403);
        }

        // Moderador = staff ou profissional; paciente é apenas participante
        $isModerator = $isStaff || $isProfessional;

        return response()->json([
            'data' => [
                'room' => $room,
                'access_url' => $this->jitsiService->getRoomAccessUrl($room, $isModerator),
                'is_moderator' => $isModerator,
            ],
        ]);
    }

    /**
     * Inicia a sala de consulta (marca como ativa).
     *
     * Apenas o profissional ou staff pode iniciar.
     */
    public function start(Request $request, ConsultationRoom $room): JsonResponse
    {
        $user = $request->user();
        $appointment = $room->appointment;

        $isStaff = $user->role?->isStaff();
        $isProfessional = $appointment->professional->user_id === $user->id;

        if (!$isStaff && !$isProfessional) {
            return response()->json(['message' => 'Apenas o profissional pode iniciar a consulta.'], 403);
        }

        try {
            $this->jitsiService->startRoom($room);

            return response()->json([
                'message' => 'Sala de consulta iniciada.',
                'data' => ['room' => $room->fresh()],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * Finaliza a sala de consulta.
     *
     * Pode ser finalizada pelo profissional ou staff.
     * Calcula automaticamente a duração real.
     */
    public function end(Request $request, ConsultationRoom $room): JsonResponse
    {
        $user = $request->user();
        $appointment = $room->appointment;

        $isStaff = $user->role?->isStaff();
        $isProfessional = $appointment->professional->user_id === $user->id;

        if (!$isStaff && !$isProfessional) {
            return response()->json(['message' => 'Apenas o profissional pode finalizar a consulta.'], 403);
        }

        $this->jitsiService->endRoom($room);

        return response()->json([
            'message' => 'Sala de consulta finalizada.',
            'data' => [
                'room' => $room->fresh(),
                'duration_minutes' => $room->duration_minutes,
            ],
        ]);
    }

    /**
     * Lista salas de consulta vinculadas a um appointment.
     */
    public function byAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $room = ConsultationRoom::where('appointment_id', $appointment->id)->first();

        if (!$room) {
            return response()->json([
                'message' => 'Nenhuma sala de consulta encontrada para este agendamento.',
                'data' => null,
            ]);
        }

        return response()->json(['data' => $room]);
    }
}