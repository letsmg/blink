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
