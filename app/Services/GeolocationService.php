<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGeolocation;
use App\Models\VisitorGeolocation;
use Illuminate\Http\Request;

/**
 * Serviço de geolocalização - coleta e armazena IP e geolocalização.
 *
 * Utiliza a API gratuita ip-api.com (sem necessidade de chave, 45 req/min).
 * Mantém histórico separado para visitantes (não logados) e usuários logados.
 *
 * Intenção: Rastrear conformidade LGPD com registro de IP e localização
 * no momento do aceite dos termos e em acessos autenticados.
 */
class GeolocationService
{
    /**
     * Registra a geolocalização de um visitante (não logado).
     */
    public function recordVisitor(Request $request, ?string $termType = null, ?string $termsVersion = null): VisitorGeolocation
    {
        $geoData = $this->getGeoLocation($request->ip());

        return VisitorGeolocation::create([
            'ip_address' => $request->ip(),
            'country' => $geoData['country'] ?? null,
            'region' => $geoData['region'] ?? null,
            'city' => $geoData['city'] ?? null,
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
            'user_agent' => $request->userAgent(),
            'term_type' => $termType,
            'terms_version' => $termsVersion ?? '1.0',
        ]);
    }

    /**
     * Registra a geolocalização de um usuário logado.
     */
    public function recordUser(Request $request, User $user, ?string $termType = null, ?string $termsVersion = null): UserGeolocation
    {
        $geoData = $this->getGeoLocation($request->ip());

        return UserGeolocation::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'country' => $geoData['country'] ?? null,
            'region' => $geoData['region'] ?? null,
            'city' => $geoData['city'] ?? null,
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
            'user_agent' => $request->userAgent(),
            'term_type' => $termType,
            'terms_version' => $termsVersion ?? '1.0',
        ]);
    }

    /**
     * Obtém dados de geolocalização a partir do IP usando ip-api.com (gratuito, sem chave).
     *
     * ip-api.com é a melhor lib gratuita disponível:
     * - 45 requisições por minuto do mesmo IP (mais que suficiente)
     * - Sem necessidade de chave de API
     * - Retorna país, região, cidade, latitude, longitude
     * - Resposta em JSON
     * - 99.9% de cobertura
     *
     * Em ambiente local, retorna dados simulados.
     */
    public function getGeoLocation(?string $ip): array
    {
        // IPs locais não têm geolocalização real
        if (! $ip || in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return [
                'country' => 'Local',
                'region' => 'Desenvolvimento',
                'city' => 'Ambiente Local',
                'latitude' => null,
                'longitude' => null,
            ];
        }

        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon");
            if ($response) {
                $data = json_decode($response, true);
                if ($data && ($data['status'] ?? '') === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Falha na geolocalização não deve quebrar o fluxo
        }

        return [];
    }
}
