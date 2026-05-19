<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação de CPF brasileiro.
 * 
 * Regras de negócio:
 * - Deve conter 11 dígitos numéricos
 * - Deve passar pelo algoritmo de validação dos dígitos verificadores
 * - Não pode ter todos os dígitos iguais (ex: 111.111.111-11)
 */
class Cpf implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove non-digit characters
        $cpf = preg_replace('/\D/', '', $value);

        // Must have exactly 11 digits
        if (strlen($cpf) !== 11) {
            $fail('O CPF deve conter 11 dígitos numéricos.');
            return;
        }

        // Reject all identical digits (e.g., 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado é inválido.');
            return;
        }

        // Validate first check digit (10th digit)
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);

        if ((int) $cpf[9] !== $digit1) {
            $fail('O CPF informado é inválido.');
            return;
        }

        // Validate second check digit (11th digit)
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);

        if ((int) $cpf[10] !== $digit2) {
            $fail('O CPF informado é inválido.');
        }
    }
}
