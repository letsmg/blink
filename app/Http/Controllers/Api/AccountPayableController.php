<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Services\AccountPayableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountPayableController extends Controller
{
    public function __construct(
        private readonly AccountPayableService $service,
    ) {}

    /**
     * Lista contas a pagar com filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->list(
            $request->only(['status', 'category', 'due_date_from', 'due_date_to']),
            $request->get('per_page', 15),
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Cria nova conta a pagar.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description'     => ['required', 'string', 'max:500'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'due_date'        => ['required', 'date'],
            'category'        => ['nullable', 'string', 'max:100'],
            'payment_method'  => ['nullable', 'string', 'max:50'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $account = $this->service->create($validated);

        return response()->json([
            'message' => 'Conta a pagar registrada com sucesso!',
            'data'    => $account,
        ], 201);
    }

    /**
     * Atualiza conta a pagar.
     */
    public function update(Request $request, AccountPayable $account): JsonResponse
    {
        $validated = $request->validate([
            'description'    => ['sometimes', 'string', 'max:500'],
            'amount'         => ['sometimes', 'numeric', 'min:0.01'],
            'due_date'       => ['sometimes', 'date'],
            'category'       => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['updated_by'] = $request->user()->id;

        $account = $this->service->update($account, $validated);

        return response()->json([
            'message' => 'Conta a pagar atualizada com sucesso!',
            'data'    => $account,
        ]);
    }

    /**
     * Marca conta como paga.
     */
    public function markAsPaid(Request $request, AccountPayable $account): JsonResponse
    {
        $validated = $request->validate([
            'paid_at'        => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['updated_by'] = $request->user()->id;

        $account = $this->service->markAsPaid($account, $validated);

        return response()->json([
            'message' => 'Conta marcada como paga com sucesso!',
            'data'    => $account,
        ]);
    }

    /**
     * Remove conta a pagar (SoftDelete).
     */
    public function destroy(AccountPayable $account): JsonResponse
    {
        $this->service->delete($account);

        return response()->json(['message' => 'Conta a pagar removida com sucesso!']);
    }

    /**
     * Dashboard — totais para cards no front-end.
     */
    public function totals(): JsonResponse
    {
        return response()->json([
            'total_pending' => $this->service->totalPending(),
            'total_overdue' => $this->service->totalOverdue(),
        ]);
    }
}