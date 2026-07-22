<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação de CNPJ (Cadastro Nacional da Pessoa Jurídica).
 * 
 * Suporta tanto o formato tradicional (apenas números) quanto o novo modelo
 * alfanumérico definido pela Receita Federal (RFB) a partir de 2026.
 * 
 * O algoritmo de validação alfanumérico converte caracteres A-Z usando
 * o valor ASCII oficial: ord(char) - 48, conforme especificação da RFB.
 */
class Cnpj implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/[^A-Za-z0-9]/', '', (string) $value);

        if (strlen($cnpj) !== 14) {
            $fail('O CNPJ deve conter exatamente 14 caracteres.');
            return;
        }

        if (! $this->validateAlphanumericCnpj($cnpj)) {
            $fail('O CNPJ informado é inválido.');
        }
    }

    /**
     * Valida CNPJ alfanumérico conforme novo padrão da Receita Federal.
     * 
     * Compatível retroativamente com CNPJs puramente numéricos.
     *
     * @see https://www.gov.br/receitafederal/pt-br/assuntos/orientacao-tributaria/cadastros/cnpj
     */
    public function validateAlphanumericCnpj(string $cnpj): bool
    {
        // Função para converter caractere no valor do novo padrão RFB
        $converterChar = function ($char) {
            return ord(strtoupper($char)) - 48;
        };

        // Validação do Primeiro Dígito Verificador
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma1 += $converterChar($cnpj[$i]) * $pesos1[$i];
        }
        $resto1 = $soma1 % 11;
        $dv1 = ($resto1 < 2) ? 0 : 11 - $resto1;

        if ((int) $cnpj[12] !== $dv1) {
            return false;
        }

        // Validação do Segundo Dígito Verificador
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma2 += $converterChar($cnpj[$i]) * $pesos2[$i];
        }
        $resto2 = $soma2 % 11;
        $dv2 = ($resto2 < 2) ? 0 : 11 - $resto2;

        return (int) $cnpj[13] === $dv2;
    }
}