<?php

namespace App\Repositories;

use App\Models\AccountReceivable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountReceivableRepository extends BaseRepository
{
    public function __construct(AccountReceivable $model)
    {
        parent::__construct($model);
    }

    /**
     * Lista contas a receber com filtros opcionais.
     */
    public function listFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->with(['patient:id,user_id', 'patient.user:id,display_name', 'appointment:id,date,time', 'creator:id,name,email']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
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
     * Busca contas a receber de um paciente específico.
     */
    public function findByPatient(int $patientId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listFiltered(['patient_id' => $patientId], $perPage);
    }

    /**
     * Soma total de recebimentos por status.
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
    public function markAsPaid(AccountReceivable $account, array $data): AccountReceivable
    {
        $account->update([
            'status' => 'paid',
            'paid_at' => $data['paid_at'] ?? now(),
            'payment_method' => $data['payment_method'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ]);

        return $account->fresh();
    }
}