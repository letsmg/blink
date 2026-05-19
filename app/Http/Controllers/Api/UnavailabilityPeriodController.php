<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnavailabilityPeriodRequest;
use App\Models\Professional;
use App\Models\UnavailabilityPeriod;
use App\Services\UnavailabilityService;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciar períodos de indisponibilidade de profissionais.
 * Acessível apenas por Staff (Admin + Operational).
 */
class UnavailabilityPeriodController extends Controller
{
    public function __construct(
        private readonly UnavailabilityService $unavailabilityService,
    ) {}

    /**
     * Lista todos os períodos de indisponibilidade de um profissional.
     * GET /api/staff/professionals/{professional}/unavailability
     */
    public function index(Professional $professional): JsonResponse
    {
        $periods = $this->unavailabilityService->listByProfessional($professional->id);

        return response()->json(['data' => $periods]);
    }

    /**
     * Lista apenas períodos futuros (para calendário).
     * GET /api/staff/professionals/{professional}/unavailability/future
     */
    public function future(Professional $professional): JsonResponse
    {
        $periods = $this->unavailabilityService->listFutureByProfessional($professional->id);

        return response()->json(['data' => $periods]);
    }

    /**
     * Cria um novo período de indisponibilidade.
     * POST /api/staff/professionals/{professional}/unavailability
     */
    public function store(StoreUnavailabilityPeriodRequest $request, Professional $professional): JsonResponse
    {
        try {
            $period = $this->unavailabilityService->create(
                $professional->id,
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('reason'),
            );

            return response()->json([
                'message' => 'Período de indisponibilidade cadastrado com sucesso!',
                'data' => $period,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Atualiza um período de indisponibilidade.
     * PUT /api/staff/professionals/{professional}/unavailability/{period}
     */
    public function update(StoreUnavailabilityPeriodRequest $request, Professional $professional, UnavailabilityPeriod $period): JsonResponse
    {
        // Garante que o período pertence ao profissional informado
        if ($period->professional_id !== $professional->id) {
            return response()->json(['message' => 'Período não pertence a este profissional.'], 403);
        }

        try {
            $updated = $this->unavailabilityService->update(
                $period,
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('reason'),
            );

            return response()->json([
                'message' => 'Período de indisponibilidade atualizado com sucesso!',
                'data' => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove um período de indisponibilidade.
     * DELETE /api/staff/professionals/{professional}/unavailability/{period}
     */
    public function destroy(Professional $professional, UnavailabilityPeriod $period): JsonResponse
    {
        if ($period->professional_id !== $professional->id) {
            return response()->json(['message' => 'Período não pertence a este profissional.'], 403);
        }

        $this->unavailabilityService->delete($period);

        return response()->json(['message' => 'Período de indisponibilidade removido com sucesso!']);
    }
}
