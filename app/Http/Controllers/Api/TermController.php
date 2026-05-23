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
     * Salva permanentemente no banco de dados o visitor_uuid, IP, geolocalização
     * e user-agent no momento do aceite.
     *
     * Regras LGPD seguidas:
     * - NENHUMA coleta de dados é feita antes do aceite explícito
     * - O visitor_uuid identifica anonimamente o visitante
     * - A geolocalização é coletada via backend (GeoIP) no momento do aceite
     * - O aceite fica registrado permanentemente no banco (não apenas em cookie/sessão)
     */
    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term_type' => 'required|in:terms_of_use,privacy_policy,both',
            'terms_version' => 'nullable|string|max:20',
            'visitor_uuid' => 'nullable|string|max:36',
        ]);

        $termType = $validated['term_type'];
        $termsVersion = $validated['terms_version'] ?? '1.0';
        $visitorUuid = $validated['visitor_uuid'] ?? null;

        // Coleta geolocalização via backend (GeoIP) APENAS no momento do aceite
        // Usa o IP do visitante para resolver localização aproximada
        $geoData = $this->geolocationService->locate($request->ip());

        // Registra o aceite na tabela term_acceptances
        $acceptance = TermAcceptance::create([
            'visitor_uuid' => $visitorUuid,
            'user_id' => $request->user()?->id,
            'term_type' => $termType,
            'ip_address' => $request->ip(),
            'country' => $geoData['country'] ?? null,
            'region' => $geoData['region'] ?? null,
            'city' => $geoData['city'] ?? null,
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
            'user_agent' => $request->userAgent(),
            'terms_version' => $termsVersion,
        ]);

        // Se for um usuário logado, atualiza também o campo terms_accepted no users table
        if ($request->user()) {
            $request->user()->update([
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version' => $termsVersion,
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
     * Para visitantes não logados, verifica pelo visitor_uuid.
     * Para usuários logados, verifica pelo user_id.
     */
    public function check(Request $request): JsonResponse
    {
        $query = TermAcceptance::query();

        if ($request->user()) {
            // Usuário logado: verifica pelo user_id
            $query->where('user_id', $request->user()->id);
        } else {
            // Visitante não logado: verifica pelo visitor_uuid (enviado como query param)
            $visitorUuid = $request->query('visitor_uuid');
            if ($visitorUuid) {
                $query->where('visitor_uuid', $visitorUuid);
            } else {
                // Fallback: verifica pelo IP (para compatibilidade)
                $query->where('ip_address', $request->ip());
            }
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

    /**
     * Vincula um visitor_uuid a um user_id (consolidação histórica).
     *
     * Chamado durante o registro do usuário para associar o aceite anônimo
     * anterior ao novo usuário criado.
     */
    public function linkVisitorToUser(string $visitorUuid, int $userId): void
    {
        TermAcceptance::where('visitor_uuid', $visitorUuid)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);
    }
}
