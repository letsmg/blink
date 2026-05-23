<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermAcceptance;
use App\Services\GeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function __construct(
        private readonly GeolocationService $geolocationService,
    ) {}

    /**
     * Registra o aceite dos termos por um visitante ou usuário logado.
     *
     * Salva permanentemente no banco de dados o IP, geolocalização e
     * user-agent no momento do aceite.
     * Mantém histórico separado para visitantes (VisitorGeolocation)
     * e usuários logados (UserGeolocation).
     */
    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term_type' => 'required|in:terms_of_use,privacy_policy,both',
            'terms_version' => 'nullable|string|max:20',
        ]);

        $termType = $validated['term_type'];
        $termsVersion = $validated['terms_version'] ?? '1.0';

        // Registra geolocalização separadamente:
        // - Visitantes (não logados) vão para visitor_geolocations
        // - Usuários logados vão para user_geolocations
        if ($request->user()) {
            $this->geolocationService->recordUser($request, $request->user(), $termType, $termsVersion);
        } else {
            $this->geolocationService->recordVisitor($request, $termType, $termsVersion);
        }

        // Registra o aceite na tabela term_acceptances (mantida para compatibilidade)
        $acceptance = TermAcceptance::create([
            'user_id' => $request->user()?->id,
            'term_type' => $termType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'terms_version' => $termsVersion,
        ]);

        // Se for um usuário logado, atualiza também o campo terms_accepted no users table
        if ($request->user()) {
            $request->user()->update([
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Termos aceitos com sucesso!',
            'acceptance' => $acceptance,
        ], 201);
    }

    /**
     * Verifica se o visitante atual já aceitou os termos.
     *
     * Para visitantes não logados, verifica pelo IP.
     * Para usuários logados, verifica pelo user_id.
     */
    public function check(Request $request): JsonResponse
    {
        $query = TermAcceptance::query();

        if ($request->user()) {
            // Usuário logado: verifica pelo user_id
            $query->where('user_id', $request->user()->id);
        } else {
            // Visitante não logado: verifica pelo IP
            $query->where('ip_address', $request->ip());
        }

        // Verifica se existe um aceite do tipo 'both' ou 'terms_of_use'
        $hasAccepted = $query->where(function ($q) {
            $q->where('term_type', 'both')
                ->orWhere('term_type', 'terms_of_use');
        })->exists();

        return response()->json([
            'accepted' => $hasAccepted,
        ]);
    }
}
