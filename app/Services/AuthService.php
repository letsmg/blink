<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CpfEncryptionService $cpfEncryption,
    ) {}

    /**
     * Register a new patient user.
     */
    public function registerPatient(array $data): array
    {
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Patient,
        ]);

        // Create patient profile with encrypted CPF
        $cpfData = $this->cpfEncryption->encrypt($data['cpf']);
        $user->patient()->create([
            'full_name' => $data['name'],
            'date_of_birth' => $data['date_of_birth'],
            'cpf_encrypted' => $cpfData['encrypted'],
            'cpf_hash' => $cpfData['hash'],
            'main_complaint' => $data['main_complaint'] ?? null,
            'street' => $data['street'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
            'clinical_history' => $data['clinical_history'] ?? null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Authenticate user and generate token.
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Revoke old tokens and create new one
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Logout user by revoking current token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Accept terms of use.
     */
    public function acceptTerms(User $user): User
    {
        $user->update([
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
        ]);

        return $user->fresh();
    }
}
