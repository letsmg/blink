<?php

namespace App\Repositories;

use App\Models\UnavailabilityPeriod;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para gerenciar períodos de indisponibilidade de profissionais.
 */
class UnavailabilityPeriodRepository extends BaseRepository
{
    public function __construct(UnavailabilityPeriod $model)
    {
        parent::__construct($model);
    }

    /**
     * Retorna todos os períodos de indisponibilidade de um profissional.
     */
    public function findByProfessional(int $professionalId): Collection
    {
        return $this->model
            ->where('professional_id', $professionalId)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Retorna períodos futuros de indisponibilidade de um profissional.
     */
    public function findFutureByProfessional(int $professionalId): Collection
    {
        return $this->model
            ->where('professional_id', $professionalId)
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Verifica se uma data específica está dentro de algum período de indisponibilidade.
     */
    public function isDateUnavailable(int $professionalId, string $date): bool
    {
        return $this->model
            ->where('professional_id', $professionalId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Verifica se há conflito (sobreposição) com períodos existentes.
     */
    public function hasOverlap(int $professionalId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('professional_id', $professionalId)
            ->where(function ($q) use ($startDate, $endDate) {
                // Novo período começa dentro de um existente
                $q->whereBetween('start_date', [$startDate, $endDate])
                  // Novo período termina dentro de um existente
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  // Novo período engloba um existente
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                           ->where('end_date', '>=', $endDate);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
