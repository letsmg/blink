<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\TermAcceptance;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CpfEncryptionService $cpfEncryption,
        private readonly PiiEncryptionService $piiEncryption,
    ) {}

    /**
     * Register a new patient user with full PII encryption.
     *
     * - Nome: first_name/last_name com hash + encrypted, display_name em texto puro
     * - CPF: encrypted + hash via CpfEncryptionService
     * - Telefones e endereço: encrypted + hash via PiiEncryptionService
     * - Consolidação histórica do visitor_uuid para aceite de termos
     */
    public function registerPatient(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Criptografa nomes para paridade PII
            $firstNameData = $this->piiEncryption->encrypt($data['first_name'] ?? explode(' ', $data['name'])[0]);
            $lastNameData = $this->piiEncryption->encrypt($data['last_name'] ?? (explode(' ', $data['name'])[1] ?? ''));

            $user = $this->userRepository->create([
                'display_name'          => $data['display_name'] ?? $data['first_name'] ?? explode(' ', $data['name'])[0],
                'first_name_hash'       => $firstNameData['hash'],
                'first_name_encrypted'  => $firstNameData['encrypted'],
                'last_name_hash'        => $lastNameData['hash'],
                'last_name_encrypted'   => $lastNameData['encrypted'],
                'email'                 => $data['email'],
                'password'              => $data['password'],
                'role'                  => UserRole::Patient->value,
            ]);

            // CPF com paridade
            $cpfData = $this->cpfEncryption->encrypt($data['cpf']);

            // Monta dados do paciente com criptografia de PII
            $patientData = [
                'date_of_birth' => $data['date_of_birth'],
                'cpf_encrypted' => $cpfData['encrypted'],
                'cpf_hash'      => $cpfData['hash'],
                'main_complaint' => $data['main_complaint'] ?? null,
                'state'          => $data['state'] ?? null,
                'zip_code'       => $data['zip_code'] ?? null,
                'clinical_history' => $data['clinical_history'] ?? null,
                'phone1'         => $data['phone1'] ?? null,
                'phone2'         => $data['phone2'] ?? null,
            ];

            // Criptografa endereço se fornecido
            if (! empty($data['street'])) {
                $street = $this->piiEncryption->encrypt($data['street']);
                $patientData['street_hash'] = $street['hash'];
                $patientData['street_encrypted'] = $street['encrypted'];
            }
            if (! empty($data['neighborhood'])) {
                $nb = $this->piiEncryption->encrypt($data['neighborhood']);
                $patientData['neighborhood_hash'] = $nb['hash'];
                $patientData['neighborhood_encrypted'] = $nb['encrypted'];
            }
            if (! empty($data['city'])) {
                $city = $this->piiEncryption->encrypt($data['city']);
                $patientData['city_hash'] = $city['hash'];
                $patientData['city_encrypted'] = $city['encrypted'];
            }

            $user->patient()->create($patientData);

            // Consolidação histórica do aceite de termos
            if (! empty($data['visitor_uuid'])) {
                TermAcceptance::where('visitor_uuid', $data['visitor_uuid'])
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->id]);
            }

            if (! empty($data['terms_accepted'])) {
                $user->update([
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                    'terms_version' => '1.0',
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });
    }

    /**
     * Authenticate user and generate token.
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

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