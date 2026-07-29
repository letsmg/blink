<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para funcionalidades da área do paciente.
 *
 * Gerencia: listagem de profissionais, envio de mensagens,
 * edição de perfil e solicitação de desativação de conta.
 */
class PatientController extends Controller
{
    /**
     * Lista todos os pacientes (área staff).
     */
    public function index(Request $request): JsonResponse
    {
        $query = \App\Models\Patient::with('user:id,display_name,email');

        // Busca por nome (via relacionamento users.display_name)
        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->whereHas('user', function ($q) use ($term) {
                $q->where('display_name', 'ilike', "%{$term}%");
            });
        }

        $patients = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json(['data' => $patients]);
    }

    /**
     * Exibe um paciente específico (área staff).
     */
    public function show(int $id): JsonResponse
    {
        $patient = \App\Models\Patient::with('user:id,display_name,email')
            ->findOrFail($id);

        return response()->json(['data' => $patient]);
    }

    /**
     * Atualiza dados de um paciente (área staff).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $patient = \App\Models\Patient::findOrFail($id);

        $validated = $request->validate([
            'main_complaint'   => ['nullable', 'string', 'max:1000'],
            'clinical_history' => ['nullable', 'string', 'max:5000'],
            'phone1'           => ['nullable', 'string', 'max:20'],
            'phone2'           => ['nullable', 'string', 'max:20'],
            'health_plan_id'   => ['nullable', 'exists:health_plans,id'],
        ]);

        $patient->update($validated);

        return response()->json([
            'message' => 'Paciente atualizado com sucesso!',
            'data' => $patient->fresh()->load('user:id,display_name,email'),
        ]);
    }

    /**
     * Lista profissionais disponíveis para o paciente enviar mensagens.
     */
    public function professionals(): JsonResponse
    {
        $professionals = Professional::with('user')
            ->get()
            ->map(function ($prof) {
                return [
                    'id' => $prof->id,
                    'name' => $prof->user?->name ?? 'Profissional',
                    'specialty' => $prof->specialty,
                ];
            });

        return response()->json(['professionals' => $professionals]);
    }

    /**
     * Envia uma mensagem do paciente para um profissional.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:professionals,id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        // Busca o user_id do profissional
        $professional = Professional::findOrFail($validated['recipient_id']);

        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $professional->user_id,
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Mensagem enviada com sucesso!',
            'data' => $message,
        ], 201);
    }

    /**
     * Retorna os dados do perfil do paciente logado.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('patient');

        return response()->json(['user' => $user]);
    }

    /**
     * Atualiza os dados do perfil do paciente logado.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$request->user()->id,
            'phone' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
        ]);

        $user = $request->user();

        // Atualiza dados do usuário
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Atualiza dados do paciente (se existir)
        if ($user->patient) {
            $user->patient->update([
                'phone' => $validated['phone'] ?? $user->patient->phone,
                'street' => $validated['street'] ?? $user->patient->street,
                'neighborhood' => $validated['neighborhood'] ?? $user->patient->neighborhood,
                'city' => $validated['city'] ?? $user->patient->city,
                'state' => $validated['state'] ?? $user->patient->state,
            ]);
        }

        // Recarrega com relacionamentos
        $user->load('patient');

        return response()->json([
            'message' => 'Dados atualizados com sucesso!',
            'user' => $user,
        ]);
    }

    /**
     * Solicita desativação da conta do paciente.
     * Cria uma mensagem administrativa para a equipe.
     */
    public function deactivateRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();

        // Cria uma mensagem para o admin (user_id = 1, primeiro admin)
        $adminUser = User::where('role', 1)->first();

        if ($adminUser) {
            Message::create([
                'sender_id' => $user->id,
                'recipient_id' => $adminUser->id,
                'subject' => 'Solicitação de Desativação de Conta',
                'content' => "O paciente {$user->name} (ID: {$user->id}, e-mail: {$user->email}) solicitou a desativação de sua conta.\n\nMotivo: ".($validated['reason'] ?? 'Não informado.'),
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Solicitação enviada com sucesso! Entraremos em contato para confirmar a desativação.',
        ]);
    }
}
