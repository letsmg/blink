<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Message;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\UnavailabilityPeriod;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing appointments (agenda).
 *
 * Intenção: Centralizar o CRUD de agendamentos, incluindo busca de pacientes
 * por nome e listagem de datas indisponíveis dos profissionais para o calendário.
 */
class AppointmentController extends Controller
{
    /**
     * List appointments with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with([
            'patient:id,full_name',
            'professional:id,full_name,specialty',
            'location:id,name',
        ]);

        if ($request->filled('professional_id')) {
            $query->where('professional_id', $request->professional_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $appointments = $query->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json(['data' => $appointments]);
    }

    /**
     * Search patients by name (for autocomplete/select2).
     */
    public function searchPatients(Request $request): JsonResponse
    {
        $term = $request->get('q', '');

        $patients = Patient::where('full_name', 'ilike', "%{$term}%")
            ->select('id', 'full_name')
            ->orderBy('full_name')
            ->limit(20)
            ->get();

        return response()->json(['data' => $patients]);
    }

    /**
     * Search professionals by name (for autocomplete/select2).
     */
    public function searchProfessionals(Request $request): JsonResponse
    {
        $term = $request->get('q', '');

        $professionals = Professional::where('full_name', 'ilike', "%{$term}%")
            ->where('is_active', true)
            ->select('id', 'full_name', 'specialty')
            ->orderBy('full_name')
            ->limit(20)
            ->get();

        return response()->json(['data' => $professionals]);
    }

    /**
     * Get unavailable dates for a professional (used by calendar).
     * Returns an array of date strings that should be blocked.
     */
    public function unavailableDates(Professional $professional): JsonResponse
    {
        $periods = $professional->unavailabilityPeriods()
            ->where('end_date', '>=', now()->subDay())
            ->get();

        $dates = [];
        foreach ($periods as $period) {
            $current = $period->start_date->copy();
            while ($current <= $period->end_date) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return response()->json(['data' => array_unique($dates)]);
    }

    /**
     * Get all unavailable dates across ALL professionals (for calendar overview).
     * Returns an array of objects with date, professional_id and professional_name.
     * Usado quando nenhum profissional específico está selecionado no filtro.
     */
    public function allUnavailableDates(): JsonResponse
    {
        $periods = UnavailabilityPeriod::with('professional:id,full_name')
            ->where('end_date', '>=', now()->subDay())
            ->get();

        $dates = [];
        foreach ($periods as $period) {
            $current = $period->start_date->copy();
            while ($current <= $period->end_date) {
                $dates[] = [
                    'date' => $current->format('Y-m-d'),
                    'professional_id' => $period->professional_id,
                    'professional_name' => $period->professional?->full_name ?? 'Profissional',
                ];
                $current->addDay();
            }
        }

        return response()->json(['data' => $dates]);
    }

    /**
     * Store a new appointment.
     * Gera automaticamente uma mensagem de notificação para o profissional.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'professional_id' => ['required', 'exists:professionals,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_return' => ['boolean'],
            'original_appointment_id' => ['nullable', 'exists:appointments,id'],
        ]);

        $appointment = Appointment::create($validated);

        // Carrega relacionamentos para montar a mensagem
        $appointment->load(['patient:id,full_name', 'professional:id,full_name,specialty,user_id', 'location:id,name']);

        // Gera mensagem automática para o profissional avisando sobre o agendamento
        if ($appointment->professional && $appointment->professional->user_id) {
            $patientName = $appointment->patient?->full_name ?? 'Paciente';
            $locationName = $appointment->location?->name ?? 'Local não informado';
            $formattedDate = Carbon::parse($appointment->date)->format('d/m/Y');
            $formattedTime = $appointment->time;

            Message::create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $appointment->professional->user_id,
                'subject' => 'Novo agendamento',
                'body' => "Olá! Um novo agendamento foi marcado:\n\n".
                    "Paciente: {$patientName}\n".
                    "Data: {$formattedDate}\n".
                    "Horário: {$formattedTime}\n".
                    "Local: {$locationName}\n".
                    ($appointment->notes ? "Observações: {$appointment->notes}\n" : '').
                    ($appointment->is_return ? "Tipo: Retorno\n" : "Tipo: Consulta inicial\n"),
            ]);
        }

        return response()->json([
            'message' => 'Agendamento realizado com sucesso!',
            'data' => $appointment->load(['patient:id,full_name', 'professional:id,full_name,specialty', 'location:id,name']),
        ], 201);
    }

    /**
     * Show a specific appointment.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'data' => $appointment->load(['patient', 'professional', 'location']),
        ]);
    }

    /**
     * Update an appointment.
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['sometimes', 'exists:locations,id'],
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_paid' => ['boolean'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'is_return' => ['boolean'],
        ]);

        $appointment->update($validated);

        return response()->json([
            'message' => 'Agendamento atualizado com sucesso!',
            'data' => $appointment->fresh()->load(['patient:id,full_name', 'professional:id,full_name,specialty', 'location:id,name']),
        ]);
    }

    /**
     * Delete an appointment.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json(['message' => 'Agendamento removido com sucesso!']);
    }

    /**
     * Count pending appointments (future or today) for the notification badge.
     * Retorna a quantidade de consultas com data futura ou até o final do dia de hoje
     * que ainda não foram pagas, para exibir um indicador visual no menu.
     */
    public function pendingCount(): JsonResponse
    {
        $count = Appointment::where('date', '>=', now()->startOfDay())
            ->where('is_paid', false)
            ->count();

        return response()->json(['pending_count' => $count]);
    }
}
