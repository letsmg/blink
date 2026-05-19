<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Service for CPF encryption/decryption using AES-256.
 * The encryption key is managed via APP_KEY in .env.
 * CPF is never stored in plain text in the database.
 */
class CpfEncryptionService
{
    /**
     * Encrypt CPF and generate a hash for uniqueness lookup.
     *
     * @return array{encrypted: string, hash: string}
     */
    public function encrypt(string $cpf): array
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);

        return [
            'encrypted' => Crypt::encryptString($cleanCpf),
            'hash' => hash('sha256', $cleanCpf),
        ];
    }

    /**
     * Decrypt an encrypted CPF.
     */
    public function decrypt(string $encryptedCpf): string
    {
        return Crypt::decryptString($encryptedCpf);
    }

    /**
     * Generate hash for CPF lookup (without encryption).
     */
    public function hash(string $cpf): string
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);
        return hash('sha256', $cleanCpf);
    }
}
