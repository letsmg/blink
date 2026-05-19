<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de integração para o gerenciamento de indisponibilidade de profissionais.
 * 
 * Valida as regras de negócio:
 * - Apenas Staff pode gerenciar
 * - CRUD completo de períodos
 * - Validação de sobreposição
 * - Validação de datas
 */
class UnavailabilityPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private Professional $professional;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria um usuário staff (Admin)
        $this->staffUser = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        // Cria um profissional
        $this->professional = Professional::factory()->create();
    }

    public function test_staff_can_list_unavailability_periods(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->getJson("/api/staff/professionals/{$this->professional->id}/unavailability");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_staff_can_create_unavailability_period(): void
    {
        $payload = [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Férias',
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data'])
            ->assertJson(['message' => 'Período de indisponibilidade cadastrado com sucesso!']);

        $this->assertDatabaseHas('unavailability_periods', [
            'professional_id' => $this->professional->id,
            'reason' => 'Férias',
        ]);
    }

    public function test_staff_cannot_create_overlapping_period(): void
    {
        // Cria primeiro período
        $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        // Tenta criar período sobreposto
        $response = $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Já existe um período de indisponibilidade que conflita com as datas informadas.']);
    }

    public function test_staff_cannot_create_period_with_end_before_start(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_staff_cannot_create_period_in_the_past(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->subDays(1)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_staff_can_update_unavailability_period(): void
    {
        // Cria período
        $createResponse = $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'reason' => 'Férias',
            ]);

        $periodId = $createResponse->json('data.id');

        // Atualiza período
        $response = $this->actingAs($this->staffUser)
            ->putJson("/api/staff/professionals/{$this->professional->id}/unavailability/{$periodId}", [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'reason' => 'Férias estendidas',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Período de indisponibilidade atualizado com sucesso!']);

        $this->assertDatabaseHas('unavailability_periods', [
            'id' => $periodId,
            'reason' => 'Férias estendidas',
        ]);
    }

    public function test_staff_can_delete_unavailability_period(): void
    {
        // Cria período
        $createResponse = $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $periodId = $createResponse->json('data.id');

        // Remove período
        $response = $this->actingAs($this->staffUser)
            ->deleteJson("/api/staff/professionals/{$this->professional->id}/unavailability/{$periodId}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Período de indisponibilidade removido com sucesso!']);

        $this->assertDatabaseMissing('unavailability_periods', ['id' => $periodId]);
    }

    public function test_patient_cannot_manage_unavailability(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $response = $this->actingAs($patient)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_staff_can_list_future_periods(): void
    {
        // Cria período passado
        $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->subDays(5)->toDateString(),
            ]);

        // Cria período futuro
        $this->actingAs($this->staffUser)
            ->postJson("/api/staff/professionals/{$this->professional->id}/unavailability", [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response = $this->actingAs($this->staffUser)
            ->getJson("/api/staff/professionals/{$this->professional->id}/unavailability/future");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
