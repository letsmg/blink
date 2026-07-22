<?php

namespace App\Services;

use App\Models\AccountReceivable;
use App\Repositories\AccountReceivableRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccountReceivableService
{
    public function __construct(
        private readonly AccountReceivableRepository $repository,
    ) {}

    /**
     * Lista contas a receber com filtros.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->listFiltered($filters, $perPage);
    }

    /**
     * Cria uma nova conta a receber vinculada a um agendamento.
     * Envolvida em transação para garantir integridade contábil-financeira.
     */
    public function create(array $data): AccountReceivable
    {
        return DB::transaction(function () use ($data) {
            // Calcula a porção do paciente como diferença entre total e cobertura
            if (! isset($data['patient_portion'])) {
                $data['patient_portion'] = $data['amount'] - ($data['insurance_covered_amount'] ?? 0);
            }

            return $this->repository->create($data);
        });
    }

    /**
     * Atualiza uma conta a receber.
     * Recalcula patient_portion se amount ou insurance_covered_amount mudarem.
     */
    public function update(AccountReceivable $account, array $data): AccountReceivable
    {
        return DB::transaction(function () use ($account, $data) {
            // Recalcula patient_portion se valores base mudarem
            if (isset($data['amount']) || isset($data['insurance_covered_amount'])) {
                $amount = $data['amount'] ?? $account->amount;
                $insurance = $data['insurance_covered_amount'] ?? $account->insurance_covered_amount;
                $data['patient_portion'] = $amount - $insurance;
            }

            $this->repository->update($account, $data);
            return $account->fresh();
        });
    }

    /**
     * Marca conta como paga, atualizando também o appointment.
     * Operação atômica: a conta e o agendamento são atualizados juntos.
     */
    public function markAsPaid(AccountReceivable $account, array $data): AccountReceivable
    {
        return DB::transaction(function () use ($account, $data) {
            $result = $this->repository->markAsPaid($account, $data);

            // Sincroniza o status de pagamento no agendamento vinculado
            if ($account->appointment) {
                $account->appointment->update([
                    'is_paid' => true,
                    'paid_at' => $data['paid_at'] ?? now(),
                    'payment_method' => $data['payment_method'] ?? $account->appointment->payment_method,
                ]);
            }

            return $result;
        });
    }

    /**
     * Remove uma conta a receber (SoftDelete).
     */
    public function delete(AccountReceivable $account): void
    {
        DB::transaction(function () use ($account) {
            $this->repository->delete($account);
        });
    }

    /**
     * Total de recebimentos pendentes.
     */
    public function totalPending(): float
    {
        return $this->repository->sumByStatus('pending');
    }

    /**
     * Total já recebido (paid).
     */
    public function totalReceived(): float
    {
        return $this->repository->sumByStatus('paid');
    }
}