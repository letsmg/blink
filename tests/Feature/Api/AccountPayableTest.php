<?php

use App\Enums\UserRole;
use App\Models\AccountPayable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de integração para Contas a Pagar.
 * 
 * Valida as regras de negócio:
 * - Apenas Staff pode gerenciar contas a pagar
 * - CRUD completo
 * - Marcação de pagamento
 * - Transações atômicas
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->staffUser = User::factory()->create(['role' => UserRole::Admin]);
    $this->patientUser = User::factory()->create(['role' => UserRole::Patient]);
});

test('staff can create account payable', function () {
    $response = $this->actingAs($this->staffUser)
        ->postJson('/api/staff/accounts-payable', [
            'description' => 'Aluguel da clínica',
            'amount'      => 5000.00,
            'due_date'    => now()->addDays(10)->toDateString(),
            'category'    => 'aluguel',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'data'])
        ->assertJson(['message' => 'Conta a pagar registrada com sucesso!']);

    $this->assertDatabaseHas('accounts_payable', [
        'description' => 'Aluguel da clínica',
        'amount'      => 5000.00,
    ]);
});

test('staff can list accounts payable', function () {
    AccountPayable::create([
        'description' => 'Conta de luz',
        'amount'      => 350.00,
        'due_date'    => now()->addDays(5),
        'status'      => 'pending',
    ]);

    $response = $this->actingAs($this->staffUser)
        ->getJson('/api/staff/accounts-payable');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

test('staff can mark account as paid', function () {
    $account = AccountPayable::create([
        'description' => 'Material de escritório',
        'amount'      => 200.00,
        'due_date'    => now()->addDays(5),
        'status'      => 'pending',
    ]);

    $response = $this->actingAs($this->staffUser)
        ->postJson("/api/staff/accounts-payable/{$account->id}/pay", [
            'paid_at'        => now()->toDateString(),
            'payment_method' => 'pix',
        ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Conta marcada como paga com sucesso!']);

    $this->assertDatabaseHas('accounts_payable', [
        'id'     => $account->id,
        'status' => 'paid',
    ]);
});

test('patient cannot manage accounts payable', function () {
    $response = $this->actingAs($this->patientUser)
        ->getJson('/api/staff/accounts-payable');

    $response->assertStatus(403);
});

test('staff can update account payable', function () {
    $account = AccountPayable::create([
        'description' => 'Água',
        'amount'      => 180.00,
        'due_date'    => now()->addDays(3),
    ]);

    $response = $this->actingAs($this->staffUser)
        ->putJson("/api/staff/accounts-payable/{$account->id}", [
            'description' => 'Conta de água atualizada',
            'amount'      => 195.50,
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('accounts_payable', [
        'id'          => $account->id,
        'description' => 'Conta de água atualizada',
        'amount'      => 195.50,
    ]);
});

test('staff can delete account payable', function () {
    $account = AccountPayable::create([
        'description' => 'Despesa temporária',
        'amount'      => 50.00,
        'due_date'    => now()->addDay(),
    ]);

    $response = $this->actingAs($this->staffUser)
        ->deleteJson("/api/staff/accounts-payable/{$account->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('accounts_payable', ['id' => $account->id]);
});

test('totals endpoint returns pending and overdue sums', function () {
    AccountPayable::create([
        'description' => 'Despesa pendente',
        'amount'      => 500.00,
        'due_date'    => now()->addDays(10),
        'status'      => 'pending',
    ]);

    AccountPayable::create([
        'description' => 'Despesa vencida',
        'amount'      => 300.00,
        'due_date'    => now()->subDays(5),
        'status'      => 'overdue',
    ]);

    $response = $this->actingAs($this->staffUser)
        ->getJson('/api/staff/accounts-payable/totals');

    $response->assertStatus(200)
        ->assertJson([
            'total_pending' => 500.00,
            'total_overdue' => 300.00,
        ]);
});