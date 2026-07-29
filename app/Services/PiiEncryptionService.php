<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Serviço genérico de criptografia PII (Personal Identifiable Information).
 * 
 * Implementa a paridade de dados exigida pelo .clinerules §3:
 * - Campos _hash: SHA-256 para busca rápida (UNIQUE INDEX, WHERE)
 * - Campos _encrypted: AES-256 para descriptografia em memória
 * 
 * Usado para: first_name, last_name, phone, street, neighborhood, city, CNPJ
 */
class PiiEncryptionService
{
    /**
     * Criptografa um valor string, retornando hash e texto criptografado.
     *
     * @return array{hash: string, encrypted: string}
     */
    public function encrypt(string $value): array
    {
        $clean = $this->normalize($value);

        return [
            'hash'      => hash('sha256', $clean),
            'encrypted' => Crypt::encryptString($clean),
        ];
    }

    /**
     * Descriptografa um valor previamente criptografado.
     */
    public function decrypt(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }

    /**
     * Gera apenas o hash de um valor (sem criptografar).
     * Usado para buscas (WHERE first_name_hash = hash('sha256', ...)).
     */
    public function hash(string $value): string
    {
        return hash('sha256', $this->normalize($value));
    }

    /**
     * Normaliza string para garantir consistência na criptografia e busca.
     * Remove espaços extras, converte para lowercase e trim.
     */
    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }
}