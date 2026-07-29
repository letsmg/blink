<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Patient;
use App\Models\User;
use App\Enums\UserRole;

/**
 * Service for handling birthday-related notifications.
 * 
 * Intenção: Verificar pacientes aniversariantes do dia e enviar
 * mensagens automáticas para todos os usuários Staff (Admin e Operacional).
 */
class BirthdayService
{
    /**
     * Send birthday notifications to all staff users.
     * 
     * Verifica pacientes que fazem aniversário hoje e envia
     * uma mensagem interna para cada staff member.
     *
     * @return int Number of messages sent
     */
    public function sendBirthdayNotifications(): int
    {
        $today = now()->format('m-d');
        $messagesSent = 0;

        // Busca pacientes que fazem aniversário hoje
        $birthdayPatients = Patient::whereRaw("TO_CHAR(date_of_birth, 'MM-DD') = ?", [$today])->get();

        if ($birthdayPatients->isEmpty()) {
            return 0;
        }

        // Busca todos os usuários Staff (Admin = role 1, Operational = role 2)
        $staffUsers = User::whereIn('role', [UserRole::Admin->value, UserRole::AdminOperational->value, UserRole::Professional->value])->get();

        if ($staffUsers->isEmpty()) {
            return 0;
        }

        // Cria uma mensagem para cada staff member com a lista de aniversariantes
        foreach ($staffUsers as $staff) {
            $patientNames = $birthdayPatients->pluck('full_name')->implode(', ');

            $subject = count($birthdayPatients) === 1
                ? "🎂 Aniversário de paciente: {$birthdayPatients->first()->full_name}"
                : "🎂 Aniversariantes do dia ({$birthdayPatients->count()} pacientes)";

            $body = "Olá! 🎉\n\n";
            $body .= "Hoje é aniversário do(s) seguinte(s) paciente(s):\n\n";

            foreach ($birthdayPatients as $patient) {
                $age = now()->diffInYears($patient->date_of_birth);
                $body .= "• {$patient->full_name} — {$age} anos\n";
            }

            $body .= "\nNão se esqueça de dar os parabéns! 🎈\n";
            $body .= "— Sistema Blink";

            // Cria a mensagem como sendo do sistema (sender_id = null ou um user admin)
            // Usamos o primeiro admin encontrado como sender, ou null se não houver
            $senderId = User::where('role', UserRole::Admin->value)->first()?->id;

            Message::create([
                'sender_id' => $senderId,
                'recipient_id' => $staff->id,
                'subject' => $subject,
                'body' => $body,
                'is_read' => false,
            ]);

            $messagesSent++;
        }

        return $messagesSent;
    }
}
