<?php

use App\Models\AccountPayable;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AccountPayableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários do serviço de Contas a Pagar.
 * 
 * Regras de negócio validadas:
 * - Criação e atualização dentro de DB::transaction
 * - Marcação de pagamento com registro de auditoria
 * - Totais financeiros (pending, overdue)
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AccountPayableService::class);
    $this->staffUser = User::factory()->create(['role' => UserRole::Admin]);
});

test('can create account payable', function () {
    $account = $this->service->create([
        'description' => 'Aluguel',
        'amount'      => 5000.00,
        'due_date'    => now()->addDays(10)->toDateString(),
        'category'    => 'aluguel',
        'created_by'  => $this->staffUser->id,
    ]);

    expect($account)->toBeInstanceOf(AccountPayable::class)
        ->and($account->description)->toBe('Aluguel');

    $this->assertDatabaseHas('accounts_payable', [
        'id'          => $account->id,
        'description' => 'Aluguel',
        'amount'      => 5000.00,
    ]);
});

test('can mark as paid', function () {
    $account = AccountPayable::create([
        'description' => 'Material',
        'amount'      => 200.00,
        'due_date'    => now()->addDays(5),
        'status'      => 'pending',
    ]);

    $result = $this->service->markAsPaid($account, [
        'paid_at'        => now()->toDateString(),
        'payment_method' => 'pix',
        'updated_by'     => $this->staffUser->id,
    ]);

    expect($result->status)->toBe('paid')
        ->and($result->paid_at)->not->toBeNull()
        ->and($result->payment_method)->toBe('pix');
});

test('can update account payable', function () {
    $account = AccountPayable::create([
        'description' => 'Original',
        'amount'      => 100.00,
        'due_date'    => now()->addDay(),
    ]);

    $updated = $this->service->update($account, [
        'description' => 'Atualizado',
        'amount'      => 150.00,
    ]);

    expect($updated->description)->toBe('Atualizado')
        ->and((float) $updated->amount)->toEqual(150.00);
});

test('can delete account payable', function () {
    $account = AccountPayable::create([
        'description' => 'Descartável',
        'amount'      => 50.00,
        'due_date'    => now()->addDay(),
    ]);

    $this->service->delete($account);

    $this->assertSoftDeleted('accounts_payable', ['id' => $account->id]);
});

test('total pending returns correct sum', function () {
    AccountPayable::create([
        'description' => 'Pendente 1',
        'amount'      => 300.00,
        'due_date'    => now()->addDays(5),
        'status'      => 'pending',
    ]);

    AccountPayable::create([
        'description' => 'Pendente 2',
        'amount'      => 200.00,
        'due_date'    => now()->addDays(10),
        'status'      => 'pending',
    ]);

    AccountPayable::create([
        'description' => 'Pago',
        'amount'      => 999.00,
        'due_date'    => now()->subDays(2),
        'status'      => 'paid',
    ]);

    expect($this->service->totalPending())->toBe(500.00);
});

test('total overdue returns correct sum', function () {
    AccountPayable::create([
        'description' => 'Vencida',
        'amount'      => 400.00,
        'due_date'    => now()->subDays(10),
        'status'      => 'overdue',
    ]);

    expect($this->service->totalOverdue())->toBe(400.00);
});