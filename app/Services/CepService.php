<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Service para busca de endereço por CEP usando BrasilAPI.
 * 
 * Intenção: Resolver cidade/UF/bairro/logradouro a partir do CEP
 * para preenchimento automático em todos os formulários com endereço.
 * Fallback para ViaCEP caso a BrasilAPI esteja indisponível.
 */
class CepService
{
    private const BRASILAPI_URL = 'https://brasilapi.com.br/api/cep/v2';
    private const VIACEP_URL = 'https://viacep.com.br/ws';

    /**
     * Busca endereço pelo CEP.
     *
     * @param string $cep CEP apenas com dígitos (8 caracteres)
     * @return array{cep: string, street: string, neighborhood: string, city: string, state: string}|null
     */
    public function lookup(string $cep): ?array
    {
        // Remove formatação, mantém apenas dígitos
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        // Tenta BrasilAPI primeiro
        $data = $this->fetchBrasilApi($cep);

        // Fallback para ViaCEP
        if ($data === null) {
            $data = $this->fetchViaCep($cep);
        }

        return $data;
    }

    private function fetchBrasilApi(string $cep): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get(self::BRASILAPI_URL . '/' . $cep);

            if ($response->successful()) {
                $json = $response->json();

                return [
                    'cep'          => $json['cep'] ?? $cep,
                    'street'       => $json['street'] ?? '',
                    'neighborhood' => $json['neighborhood'] ?? '',
                    'city'         => $json['city'] ?? '',
                    'state'        => $json['state'] ?? '',
                ];
            }
        } catch (\Exception) {
            // Fallback silencioso para ViaCEP
        }

        return null;
    }

    private function fetchViaCep(string $cep): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get(self::VIACEP_URL . '/' . $cep . '/json');

            if ($response->successful()) {
                $json = $response->json();

                // ViaCEP retorna "erro" => true quando CEP não existe
                if (! empty($json['erro'])) {
                    return null;
                }

                return [
                    'cep'          => $json['cep'] ?? $cep,
                    'street'       => $json['logradouro'] ?? '',
                    'neighborhood' => $json['bairro'] ?? '',
                    'city'         => $json['localidade'] ?? '',
                    'state'        => $json['uf'] ?? '',
                ];
            }
        } catch (\Exception) {
            // Indisponível
        }

        return null;
    }
}