<?php

namespace App\Services;

use App\Models\AccountPayable;
use App\Repositories\AccountPayableRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccountPayableService
{
    public function __construct(
        private readonly AccountPayableRepository $repository,
    ) {}

    /**
     * Lista contas a pagar com filtros.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->listFiltered($filters, $perPage);
    }

    /**
     * Cria uma nova conta a pagar.
     * Envolvida em transação para garantir integridade contábil.
     */
    public function create(array $data): AccountPayable
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    /**
     * Atualiza uma conta a pagar.
     */
    public function update(AccountPayable $account, array $data): AccountPayable
    {
        return DB::transaction(function () use ($account, $data) {
            $this->repository->update($account, $data);
            return $account->fresh();
        });
    }

    /**
     * Marca conta como paga.
     * Registra data de pagamento, método e usuário que efetuou a baixa.
     */
    public function markAsPaid(AccountPayable $account, array $data): AccountPayable
    {
        return DB::transaction(function () use ($account, $data) {
            return $this->repository->markAsPaid($account, $data);
        });
    }

    /**
     * Remove uma conta a pagar (SoftDelete).
     */
    public function delete(AccountPayable $account): void
    {
        DB::transaction(function () use ($account) {
            $this->repository->delete($account);
        });
    }

    /**
     * Total de contas pendentes para dashboard.
     */
    public function totalPending(): float
    {
        return $this->repository->sumByStatus('pending');
    }

    /**
     * Total de contas vencidas (overdue).
     */
    public function totalOverdue(): float
    {
        return $this->repository->sumByStatus('overdue');
    }
}