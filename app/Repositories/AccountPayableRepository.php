<?php

namespace App\Repositories;

use App\Models\AccountPayable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountPayableRepository extends BaseRepository
{
    public function __construct(AccountPayable $model)
    {
        parent::__construct($model);
    }

    /**
     * Lista contas a pagar com filtros opcionais.
     */
    public function listFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->with(['creator:id,name,email']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        return $query->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Conta total de despesas por status.
     */
    public function sumByStatus(string $status): float
    {
        return (float) $this->model
            ->where('status', $status)
            ->sum('amount');
    }

    /**
     * Marca como pago e registra data e método.
     */
    public function markAsPaid(AccountPayable $account, array $data): AccountPayable
    {
        $account->update([
            'status' => 'paid',
            'paid_at' => $data['paid_at'] ?? now(),
            'payment_method' => $data['payment_method'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ]);

        return $account->fresh();
    }
}