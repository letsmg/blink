<?php

namespace Tests\Unit\Services;

use App\Models\Professional;
use App\Models\UnavailabilityPeriod;
use App\Repositories\UnavailabilityPeriodRepository;
use App\Services\UnavailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do serviço de indisponibilidade de profissionais.
 * 
 * Regras de negócio validadas:
 * - Data fim deve ser >= data início
 * - Não pode haver sobreposição de períodos
 * - Verificação de data específica
 */
class UnavailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnavailabilityService $service;
    private Professional $professional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UnavailabilityService::class);
        $this->professional = Professional::factory()->create();
    }

    public function test_can_create_unavailability_period(): void
    {
        $period = $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
            'Férias',
        );

        $this->assertDatabaseHas('unavailability_periods', [
            'id' => $period->id,
            'professional_id' => $this->professional->id,
            'reason' => 'Férias',
        ]);
    }

    public function test_cannot_create_period_with_end_before_start(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A data inicial não pode ser maior que a data final.');

        $this->service->create(
            $this->professional->id,
            now()->addDays(5)->toDateString(),
            now()->addDay()->toDateString(),
        );
    }

    public function test_cannot_create_overlapping_period(): void
    {
        $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Já existe um período de indisponibilidade que conflita com as datas informadas.');

        // Tenta criar período que começa dentro do existente
        $this->service->create(
            $this->professional->id,
            now()->addDays(3)->toDateString(),
            now()->addDays(7)->toDateString(),
        );
    }

    public function test_can_create_non_overlapping_period(): void
    {
        $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        // Período após o existente - não deve conflitar
        $period = $this->service->create(
            $this->professional->id,
            now()->addDays(6)->toDateString(),
            now()->addDays(10)->toDateString(),
        );

        $this->assertNotNull($period);
        $this->assertEquals(now()->addDays(6)->toDateString(), $period->start_date->toDateString());
    }

    public function test_can_update_period(): void
    {
        $period = $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        $updated = $this->service->update(
            $period,
            now()->addDay()->toDateString(),
            now()->addDays(10)->toDateString(),
            'Férias estendidas',
        );

        $this->assertEquals('Férias estendidas', $updated->reason);
        $this->assertEquals(now()->addDays(10)->toDateString(), $updated->end_date->toDateString());
    }

    public function test_can_delete_period(): void
    {
        $period = $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        $this->service->delete($period);

        $this->assertDatabaseMissing('unavailability_periods', ['id' => $period->id]);
    }

    public function test_is_date_unavailable(): void
    {
        $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        $this->assertTrue(
            $this->service->isDateUnavailable($this->professional->id, now()->addDays(3)->toDateString())
        );

        $this->assertFalse(
            $this->service->isDateUnavailable($this->professional->id, now()->addDays(10)->toDateString())
        );
    }

    public function test_list_future_periods_excludes_past(): void
    {
        // Período passado
        $this->service->create(
            $this->professional->id,
            now()->subDays(10)->toDateString(),
            now()->subDays(5)->toDateString(),
        );

        // Período futuro
        $this->service->create(
            $this->professional->id,
            now()->addDay()->toDateString(),
            now()->addDays(5)->toDateString(),
        );

        $future = $this->service->listFutureByProfessional($this->professional->id);

        $this->assertCount(1, $future);
        $this->assertEquals(now()->addDay()->toDateString(), $future->first()->start_date->toDateString());
    }
}
