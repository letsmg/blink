<?php

use App\Enums\UserRole;
use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de integração para Contas a Receber.
 * 
 * Valida as regras de negócio:
 * - Apenas Staff pode gerenciar contas a receber
 * - Vinculação obrigatória com appointment
 * - Cálculo da patient_portion
 * - Sincronização de pagamento com appointment
 * - Transações atômicas
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->staffUser = User::factory()->create(['role' => UserRole::Admin]);
    $this->patientUser = User::factory()->create(['role' => UserRole::Patient]);

    // Cria dados base para agendamento
    $patientUserAccount = User::factory()->create(['role' => UserRole::Patient]);
    $this->patient = Patient::factory()->create(['user_id' => $patientUserAccount->id]);
    $professionalUser = User::factory()->create(['role' => UserRole::Admin]);
    $professional = Professional::factory()->create(['user_id' => $professionalUser->id]);
    $location = Location::create([
        'name' => 'Clínica Central',
        'address' => 'Rua Principal, 100',
        'zip_code' => '01000-000',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
    ]);

    $this->appointment = Appointment::create([
        'patient_id'      => $this->patient->id,
        'professional_id' => $professional->id,
        'location_id'     => $location->id,
        'date'            => now()->addDays(5),
        'time'            => '14:00',
        'is_paid'         => false,
    ]);
});

test('staff can create account receivable linked to appointment', function () {
    $response = $this->actingAs($this->staffUser)
        ->postJson('/api/staff/accounts-receivable', [
            'appointment_id'           => $this->appointment->id,
            'patient_id'               => $this->patient->id,
            'amount'                   => 300.00,
            'insurance_covered_amount' => 180.00,
            'due_date'                 => now()->addDays(10)->toDateString(),
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'data'])
        ->assertJson(['message' => 'Conta a receber registrada com sucesso!']);

    // patient_portion deve ser calculado: 300 - 180 = 120
    $this->assertDatabaseHas('accounts_receivable', [
        'appointment_id'          => $this->appointment->id,
        'amount'                  => 300.00,
        'insurance_covered_amount' => 180.00,
        'patient_portion'         => 120.00,
    ]);
});

test('staff can list accounts receivable', function () {
    AccountReceivable::create([
        'appointment_id'          => $this->appointment->id,
        'patient_id'              => $this->patient->id,
        'amount'                  => 250.00,
        'due_date'                => now()->addDays(5),
        'status'                  => 'pending',
    ]);

    $response = $this->actingAs($this->staffUser)
        ->getJson('/api/staff/accounts-receivable');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

test('staff can mark account receivable as paid and syncs appointment', function () {
    $account = AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 300.00,
        'due_date'       => now()->addDays(5),
        'status'         => 'pending',
    ]);

    $response = $this->actingAs($this->staffUser)
        ->postJson("/api/staff/accounts-receivable/{$account->id}/pay", [
            'paid_at'        => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Conta marcada como paga com sucesso!']);

    // Conta marcada como paga
    $this->assertDatabaseHas('accounts_receivable', [
        'id'     => $account->id,
        'status' => 'paid',
    ]);

    // Appointment sincronizado (is_paid = true)
    $this->assertDatabaseHas('appointments', [
        'id'      => $this->appointment->id,
        'is_paid' => true,
    ]);
});

test('patient cannot manage accounts receivable', function () {
    $response = $this->actingAs($this->patientUser)
        ->getJson('/api/staff/accounts-receivable');

    $response->assertStatus(403);
});

test('staff can update account receivable', function () {
    $account = AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 200.00,
        'due_date'       => now()->addDays(3),
    ]);

    $response = $this->actingAs($this->staffUser)
        ->putJson("/api/staff/accounts-receivable/{$account->id}", [
            'amount'                   => 250.00,
            'insurance_covered_amount' => 100.00,
        ]);

    $response->assertStatus(200);

    // patient_portion deve ser recalculado: 250 - 100 = 150
    $this->assertDatabaseHas('accounts_receivable', [
        'id'                      => $account->id,
        'amount'                  => 250.00,
        'insurance_covered_amount' => 100.00,
        'patient_portion'         => 150.00,
    ]);
});

test('staff can delete account receivable', function () {
    $account = AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 100.00,
        'due_date'       => now()->addDay(),
    ]);

    $response = $this->actingAs($this->staffUser)
        ->deleteJson("/api/staff/accounts-receivable/{$account->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('accounts_receivable', ['id' => $account->id]);
});

test('totals endpoint returns pending and received sums', function () {
    AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 500.00,
        'due_date'       => now()->addDays(10),
        'status'         => 'pending',
    ]);

    AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 700.00,
        'due_date'       => now()->subDays(5),
        'status'         => 'paid',
    ]);

    $response = $this->actingAs($this->staffUser)
        ->getJson('/api/staff/accounts-receivable/totals');

    $response->assertStatus(200)
        ->assertJson([
            'total_pending'  => 500.00,
            'total_received' => 700.00,
        ]);
});