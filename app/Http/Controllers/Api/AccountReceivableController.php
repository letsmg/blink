<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Services\AccountReceivableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountReceivableController extends Controller
{
    public function __construct(
        private readonly AccountReceivableService $service,
    ) {}

    /**
     * Lista contas a receber com filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->list(
            $request->only(['status', 'patient_id', 'due_date_from', 'due_date_to']),
            $request->get('per_page', 15),
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Cria nova conta a receber vinculada a um agendamento.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id'             => ['required', 'exists:appointments,id'],
            'patient_id'                 => ['required', 'exists:patients,id'],
            'amount'                     => ['required', 'numeric', 'min:0.01'],
            'insurance_covered_amount'   => ['nullable', 'numeric', 'min:0'],
            'due_date'                   => ['required', 'date'],
            'payment_method'             => ['nullable', 'string', 'max:50'],
            'invoice_number'             => ['nullable', 'string', 'max:50'],
            'notes'                      => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $account = $this->service->create($validated);

        return response()->json([
            'message' => 'Conta a receber registrada com sucesso!',
            'data'    => $account,
        ], 201);
    }

    /**
     * Atualiza conta a receber.
     */
    public function update(Request $request, AccountReceivable $account): JsonResponse
    {
        $validated = $request->validate([
            'amount'                   => ['sometimes', 'numeric', 'min:0.01'],
            'insurance_covered_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date'                 => ['sometimes', 'date'],
            'invoice_number'           => ['nullable', 'string', 'max:50'],
            'notes'                    => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['updated_by'] = $request->user()->id;

        $account = $this->service->update($account, $validated);

        return response()->json([
            'message' => 'Conta a receber atualizada com sucesso!',
            'data'    => $account,
        ]);
    }

    /**
     * Marca conta como paga e sincroniza com o agendamento.
     */
    public function markAsPaid(Request $request, AccountReceivable $account): JsonResponse
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
     * Remove conta a receber (SoftDelete).
     */
    public function destroy(AccountReceivable $account): JsonResponse
    {
        $this->service->delete($account);

        return response()->json(['message' => 'Conta a receber removida com sucesso!']);
    }

    /**
     * Dashboard — totais para cards no front-end.
     */
    public function totals(): JsonResponse
    {
        return response()->json([
            'total_pending'  => $this->service->totalPending(),
            'total_received' => $this->service->totalReceived(),
        ]);
    }
}