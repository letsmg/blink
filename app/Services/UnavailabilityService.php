<?php

namespace App\Services;

use App\Models\Professional;
use App\Models\UnavailabilityPeriod;
use App\Repositories\UnavailabilityPeriodRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service para gerenciar indisponibilidade de profissionais.
 * Regras de negócio: evitar sobreposição de períodos, validar datas.
 */
class UnavailabilityService
{
    public function __construct(
        private readonly UnavailabilityPeriodRepository $repository,
    ) {}

    /**
     * Lista períodos de indisponibilidade de um profissional.
     */
    public function listByProfessional(int $professionalId): Collection
    {
        return $this->repository->findByProfessional($professionalId);
    }

    /**
     * Lista apenas períodos futuros (para calendário).
     */
    public function listFutureByProfessional(int $professionalId): Collection
    {
        return $this->repository->findFutureByProfessional($professionalId);
    }

    /**
     * Cria um novo período de indisponibilidade.
     * Valida: data fim >= data início, sem sobreposição.
     *
     * @throws \InvalidArgumentException
     */
    public function create(int $professionalId, string $startDate, string $endDate, ?string $reason = null): UnavailabilityPeriod
    {
        $this->validateDates($startDate, $endDate);

        if ($this->repository->hasOverlap($professionalId, $startDate, $endDate)) {
            throw new \InvalidArgumentException('Já existe um período de indisponibilidade que conflita com as datas informadas.');
        }

        return $this->repository->create([
            'professional_id' => $professionalId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
        ]);
    }

    /**
     * Atualiza um período de indisponibilidade.
     */
    public function update(UnavailabilityPeriod $period, string $startDate, string $endDate, ?string $reason = null): UnavailabilityPeriod
    {
        $this->validateDates($startDate, $endDate);

        if ($this->repository->hasOverlap($period->professional_id, $startDate, $endDate, $period->id)) {
            throw new \InvalidArgumentException('Já existe um período de indisponibilidade que conflita com as datas informadas.');
        }

        $this->repository->update($period, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
        ]);

        return $period->fresh();
    }

    /**
     * Remove um período de indisponibilidade.
     */
    public function delete(UnavailabilityPeriod $period): void
    {
        $this->repository->delete($period);
    }

    /**
     * Verifica se uma data está indisponível para um profissional.
     */
    public function isDateUnavailable(int $professionalId, string $date): bool
    {
        return $this->repository->isDateUnavailable($professionalId, $date);
    }

    /**
     * Valida as regras de data.
     */
    private function validateDates(string $startDate, string $endDate): void
    {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('A data inicial não pode ser maior que a data final.');
        }
    }
}
