<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SanitizeInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SanitizeInputTest extends TestCase
{
    private SanitizeInput $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SanitizeInput;
    }

    #[DataProvider('sanitizationProvider')]
    public function test_sanitizes_input_data(array $input, array $expected): void
    {
        $request = Request::create('/api/test', 'POST', $input);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $request->input($key), "Campo {$key} não foi sanitizado corretamente.");
        }
    }

    public function test_skips_sanitization_on_get_requests(): void
    {
        $request = Request::create('/api/test', 'GET', ['name' => '  João  ']);

        $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        // GET requests should not be sanitized
        $this->assertEquals('  João  ', $request->input('name'));
    }

    public static function sanitizationProvider(): array
    {
        return [
            'trim básico' => [
                ['name' => '  João Silva  '],
                ['name' => 'João Silva'],
            ],
            'string vazia vira null' => [
                ['name' => '   '],
                ['name' => null],
            ],
            'email em maiúsculo' => [
                ['email' => '  TESTE@EMAIL.COM  '],
                ['email' => 'TESTE@EMAIL.COM'],
            ],
            'múltiplos campos' => [
                [
                    'name' => '  Maria  ',
                    'email' => '  maria@teste.com  ',
                    'city' => '  São Paulo  ',
                ],
                [
                    'name' => 'Maria',
                    'email' => 'maria@teste.com',
                    'city' => 'São Paulo',
                ],
            ],
            'array aninhado' => [
                [
                    'address' => [
                        'street' => '  Rua A  ',
                        'city' => '  Cidade  ',
                    ],
                ],
                [
                    'address.street' => 'Rua A',
                    'address.city' => 'Cidade',
                ],
            ],
        ];
    }
}
