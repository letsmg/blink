<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ConsultationRoom;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

/**
 * Serviço de gerenciamento de salas de teleatendimento via Jitsi Meet.
 *
 * Integração com Jitsi Meet self-hosted (Docker).
 * Gera tokens JWT para autenticação segura nas salas,
 * garantindo que apenas profissional e paciente tenham acesso.
 */
class JitsiVideoRoomService
{
    /**
     * URL base do servidor Jitsi Meet (definido no .env).
     */
    private string $baseUrl;

    /**
     * ID da aplicação Jitsi (JWT App ID).
     */
    private string $appId;

    /**
     * Secret compartilhado para assinatura JWT.
     */
    private string $appSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('JITSI_PUBLIC_URL', 'http://localhost:8080'), '/');
        $this->appId = env('JITSI_JWT_APP_ID', 'blink_app');
        $this->appSecret = env('JITSI_JWT_APP_SECRET', 'blink_jitsi_secret_change_me');
    }

    /**
     * Cria uma nova sala de consulta para um agendamento telehealth.
     *
     * @param Appointment $appointment Deve ser do tipo 'telehealth'
     * @param string $professionalName Nome de exibição do profissional
     * @param string $patientName Nome de exibição do paciente
     * @return ConsultationRoom
     */
    public function createRoom(Appointment $appointment, string $professionalName, string $patientName): ConsultationRoom
    {
        // Verifica se já existe sala para este appointment (idempotência)
        $existingRoom = ConsultationRoom::where('appointment_id', $appointment->id)->first();
        if ($existingRoom) {
            return $existingRoom;
        }

        // Nome único da sala: consulta-{appointment_id}-{uuid_curto}
        $roomName = 'consulta-' . $appointment->id . '-' . Str::random(8);

        // Gera tokens JWT separados: moderador (profissional) e participante (paciente)
        $moderatorToken = $this->generateJwtToken($roomName, $professionalName, true);
        $participantToken = $this->generateJwtToken($roomName, $patientName, false);

        // URL completa da sala
        $roomUrl = $this->baseUrl . '/' . $roomName;

        // Persiste a sala no banco
        $room = ConsultationRoom::create([
            'appointment_id' => $appointment->id,
            'room_name' => $roomName,
            'room_url' => $roomUrl,
            'moderator_token' => $moderatorToken,
            'participant_token' => $participantToken,
            'status' => 'created',
            'metadata' => json_encode([
                'created_at' => now()->toIso8601String(),
                'professional_id' => $appointment->professional_id,
                'patient_id' => $appointment->patient_id,
                'created_by' => 'system',
            ]),
        ]);

        Log::info('Sala Jitsi criada', [
            'appointment_id' => $appointment->id,
            'room_name' => $roomName,
            'room_id' => $room->id,
        ]);

        return $room;
    }

    /**
     * Gera um token JWT assinado com HS256 para autenticação no Jitsi.
     *
     * @param string $roomName Nome da sala
     * @param string $displayName Nome de exibição do usuário
     * @param bool $isModerator Se true, usuário é moderador (profissional)
     * @return string Token JWT completo
     */
    private function generateJwtToken(string $roomName, string $displayName, bool $isModerator): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $payload = [
            'context' => [
                'user' => [
                    'name' => $displayName,
                ],
            ],
            'aud' => 'jitsi',
            'iss' => $this->appId,
            'sub' => $this->baseUrl,
            'room' => $roomName,
            'exp' => $now + 86400, // Token expira em 24h (tempo máximo da sala)
            'nbf' => $now - 10,    // 10s de tolerância para clock skew
            'iat' => $now,
            'moderator' => $isModerator,
        ];

        // Codifica header e payload em Base64URL
        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));

        // Assina com HMAC-SHA256
        $signature = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $this->appSecret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return "{$encodedHeader}.{$encodedPayload}.{$encodedSignature}";
    }

    /**
     * Codifica string em Base64URL (RFC 7515).
     * Remove padding '=' e substitui caracteres especiais.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Inicia uma sala de consulta (marca como ativa).
     */
    public function startRoom(ConsultationRoom $room): ConsultationRoom
    {
        if ($room->status !== 'created') {
            throw new \RuntimeException('Sala não pode ser iniciada. Status atual: ' . $room->status);
        }

        $room->status = 'active';
        $room->started_at = now();
        $room->save();

        // Atualiza também o appointment
        $room->appointment->started_at = now();
        $room->appointment->save();

        Log::info('Sala Jitsi iniciada', ['room_id' => $room->id]);

        return $room;
    }

    /**
     * Finaliza uma sala de consulta.
     */
    public function endRoom(ConsultationRoom $room): ConsultationRoom
    {
        if ($room->isFinished()) {
            return $room;
        }

        $room->end();

        // Atualiza também o appointment
        $appointment = $room->appointment;
        $appointment->ended_at = now();
        $appointment->save();

        Log::info('Sala Jitsi finalizada', [
            'room_id' => $room->id,
            'duration_minutes' => $room->duration_minutes,
        ]);

        return $room;
    }

    /**
     * Obtém a URL de acesso à sala para um tipo de usuário específico.
     *
     * @param ConsultationRoom $room
     * @param bool $isModerator Se true, retorna URL com token de moderador
     * @return string URL completa com token JWT embutido
     */
    public function getRoomAccessUrl(ConsultationRoom $room, bool $isModerator): string
    {
        $token = $isModerator ? $room->moderator_token : $room->participant_token;

        return $room->room_url . '?jwt=' . urlencode($token);
    }
}