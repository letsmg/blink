<?php

namespace Tests\Unit\Rules;

use App\Rules\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfTest extends TestCase
{
    private Cpf $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Cpf;
    }

    #[DataProvider('validCpfProvider')]
    public function test_valid_cpf_passes(string $cpf): void
    {
        $failed = false;
        $fail = function () use (&$failed) {
            $failed = true;
        };

        $this->rule->validate('cpf', $cpf, $fail);
        $this->assertFalse($failed, "CPF válido {$cpf} deveria passar na validação.");
    }

    #[DataProvider('invalidCpfProvider')]
    public function test_invalid_cpf_fails(string $cpf): void
    {
        $failed = false;
        $fail = function () use (&$failed) {
            $failed = true;
        };

        $this->rule->validate('cpf', $cpf, $fail);
        $this->assertTrue($failed, "CPF inválido {$cpf} deveria falhar na validação.");
    }

    /**
     * CPFs válidos gerados aleatoriamente para teste.
     */
    public static function validCpfProvider(): array
    {
        return [
            'CPF com máscara' => ['529.982.247-25'],
            'CPF sem máscara' => ['52998224725'],
            'CPF válido 1' => ['123.456.789-09'],
            'CPF válido 2' => ['935.411.347-80'],
            'CPF válido 3' => ['52998224725'], // mesmo CPF sem máscara
        ];
    }

    /**
     * CPFs inválidos para testar todas as regras de rejeição.
     */
    public static function invalidCpfProvider(): array
    {
        return [
            'todos dígitos iguais' => ['111.111.111-11'],
            'sequência' => ['123.456.789-00'],
            'menos de 11 dígitos' => ['123.456.789'],
            'mais de 11 dígitos' => ['123.456.789-123'],
            'vazio' => [''],
            'com letras' => ['abc.def.ghi-jk'],
            'primeiro dígito verificador inválido' => ['529.982.247-26'],
            'segundo dígito verificador inválido' => ['529.982.247-24'],
            'zeros' => ['000.000.000-00'],
        ];
    }
}
