<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\Cpf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de registro de paciente.
 * 
 * Valida as regras de negócio:
 * - Sanitização de entrada (trim, lowercase email)
 * - Validação estrutural de CPF
 * - Criptografia do CPF no banco
 * - Geração de token JWT
 * - Role atribuída como Patient (0)
 */
class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validPayload = [
            'name' => 'Maria Paciente',
            'email' => 'maria@teste.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'cpf' => '529.982.247-25',
            'date_of_birth' => '1990-05-15',
        ];
    }

    public function test_can_register_patient_successfully(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria@teste.com',
            'role' => UserRole::Patient->value,
        ]);

        $this->assertDatabaseHas('patients', [
            'full_name' => 'Maria Paciente',
        ]);
    }

    public function test_cpf_is_encrypted_in_database(): void
    {
        $this->postJson('/api/register', $this->validPayload);

        $patient = \App\Models\Patient::first();

        // CPF must be encrypted, not stored in plain text
        $this->assertNotNull($patient->cpf_encrypted);
        $this->assertNotEquals('52998224725', $patient->cpf_encrypted);
        $this->assertStringStartsWith('ey', $patient->cpf_encrypted, 'CPF deve estar em formato criptografado (base64)');
    }

    public function test_cannot_register_with_duplicate_email(): void
    {
        $this->postJson('/api/register', $this->validPayload);

        $response = $this->postJson('/api/register', $this->validPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_register_with_invalid_cpf(): void
    {
        $payload = $this->validPayload;
        $payload['cpf'] = '111.111.111-11';

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_cannot_register_without_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'cpf', 'date_of_birth']);
    }

    public function test_email_is_sanitized_to_lowercase(): void
    {
        $payload = $this->validPayload;
        $payload['email'] = '  MARIA@TESTE.COM  ';

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'maria@teste.com']);
    }

    public function test_password_must_have_minimum_8_characters(): void
    {
        $payload = $this->validPayload;
        $payload['password'] = '123';
        $payload['password_confirmation'] = '123';

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_must_be_confirmed(): void
    {
        $payload = $this->validPayload;
        $payload['password_confirmation'] = 'different';

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_returns_token_on_successful_registration(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload);

        $response->assertStatus(201);
        $token = $response->json('token');
        $this->assertNotNull($token);
        $this->assertStringContainsString('|', $token, 'Token deve conter o separador de ID');
    }

    public function test_user_role_is_patient(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload);

        $response->assertStatus(201);
        $this->assertEquals(UserRole::Patient->value, $response->json('user.role'));
    }
}
