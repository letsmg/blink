<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CepService;
use Illuminate\Http\JsonResponse;

/**
 * Controller para busca de endereço por CEP.
 * 
 * Intenção: Expor endpoint público usado em formulários para preenchimento
 * automático de cidade/UF/bairro/logradouro a partir do CEP digitado.
 */
class CepController extends Controller
{
    public function __construct(
        private readonly CepService $cepService,
    ) {}

    /**
     * Lookup address by CEP.
     */
    public function lookup(string $cep): JsonResponse
    {
        $data = $this->cepService->lookup($cep);

        if ($data === null) {
            return response()->json([
                'message' => 'CEP não encontrado ou serviço indisponível.',
            ], 404);
        }

        return response()->json(['data' => $data]);
    }
}