<?php

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AccountReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do serviço de Contas a Receber.
 * 
 * Regras de negócio validadas:
 * - Cálculo automático da patient_portion
 * - Sincronização atômica com appointment no pagamento
 * - Operações em DB::transaction
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AccountReceivableService::class);
    $this->staffUser = User::factory()->create(['role' => UserRole::Admin]);

    // Cria estrutura base
    $patientUser = User::factory()->create(['role' => UserRole::Patient]);
    $this->patient = Patient::factory()->create(['user_id' => $patientUser->id]);
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
        'time'            => '10:00',
        'is_paid'         => false,
    ]);
});

test('creates account receivable with calculated patient portion', function () {
    $account = $this->service->create([
        'appointment_id'           => $this->appointment->id,
        'patient_id'               => $this->patient->id,
        'amount'                   => 500.00,
        'insurance_covered_amount' => 300.00,
        'due_date'                 => now()->addDays(10)->toDateString(),
        'created_by'               => $this->staffUser->id,
    ]);

    // patient_portion = 500 - 300 = 200
    expect($account)->toBeInstanceOf(AccountReceivable::class);

    $this->assertDatabaseHas('accounts_receivable', [
        'id'                      => $account->id,
        'patient_portion'         => 200.00,
        'insurance_covered_amount' => 300.00,
    ]);
});

test('creates account with full patient portion when no insurance', function () {
    $account = $this->service->create([
        'appointment_id'           => $this->appointment->id,
        'patient_id'               => $this->patient->id,
        'amount'                   => 300.00,
        'due_date'                 => now()->addDays(10)->toDateString(),
        'created_by'               => $this->staffUser->id,
    ]);

    // Sem cobertura de convênio, patient_portion = amount
    expect((float) $account->patient_portion)->toEqual(300.00)
        ->and((float) $account->insurance_covered_amount)->toEqual(0.00);
});

test('mark as paid syncs appointment status', function () {
    $account = AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 300.00,
        'due_date'       => now()->addDay(),
        'status'         => 'pending',
    ]);

    $result = $this->service->markAsPaid($account, [
        'paid_at'        => now()->toDateString(),
        'payment_method' => 'debit',
        'updated_by'     => $this->staffUser->id,
    ]);

    expect($result->status)->toBe('paid');

    // Appointment deve estar sincronizado como pago
    $this->assertDatabaseHas('appointments', [
        'id'      => $this->appointment->id,
        'is_paid' => true,
    ]);
});

test('updates recalculates patient portion', function () {
    $account = AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 400.00,
        'due_date'       => now()->addDay(),
    ]);

    $updated = $this->service->update($account, [
        'amount'                   => 500.00,
        'insurance_covered_amount' => 200.00,
    ]);

    expect((float) $updated->amount)->toEqual(500.00)
        ->and((float) $updated->insurance_covered_amount)->toEqual(200.00)
        ->and((float) $updated->patient_portion)->toEqual(300.00);
});

test('can delete account receivable', function () {
    $account = AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 100.00,
        'due_date'       => now()->addDay(),
    ]);

    $this->service->delete($account);

    $this->assertSoftDeleted('accounts_receivable', ['id' => $account->id]);
});

test('total pending returns correct sum', function () {
    AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 150.00,
        'due_date'       => now()->addDays(5),
        'status'         => 'pending',
    ]);

    AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 250.00,
        'due_date'       => now()->addDays(10),
        'status'         => 'pending',
    ]);

    expect($this->service->totalPending())->toBe(400.00);
});

test('total received returns correct sum', function () {
    AccountReceivable::create([
        'appointment_id' => $this->appointment->id,
        'patient_id'     => $this->patient->id,
        'amount'         => 600.00,
        'due_date'       => now()->subDays(5),
        'status'         => 'paid',
    ]);

    expect($this->service->totalReceived())->toBe(600.00);
});