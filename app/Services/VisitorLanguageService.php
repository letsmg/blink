<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VisitorLanguageService
{
    public function getSupportedLocales(): array
    {
        return config('app.supported_locales', ['pt', 'en', 'es']);
    }

    public function detectLocaleFromAcceptLanguage(?string $acceptLanguageHeader): ?string
    {
        if (! $acceptLanguageHeader) {
            return null;
        }

        $accepted = explode(',', $acceptLanguageHeader);

        foreach ($accepted as $language) {
            $candidate = $this->normalizeLocale($language);
            if ($candidate && in_array($candidate, $this->getSupportedLocales(), true)) {
                return $candidate;
            }
        }

        return null;
    }

    public function normalizeLocale(string $language): ?string
    {
        $language = strtolower(trim(explode(';', $language)[0]));

        if ($language === '') {
            return null;
        }

        if (str_contains($language, '-')) {
            $language = explode('-', $language)[0];
        }

        return match ($language) {
            'pt', 'pt-br', 'pt-pt' => 'pt',
            'es', 'es-es', 'es-mx', 'es-ar' => 'es',
            'en', 'en-us', 'en-gb', 'en-au', 'en-ca' => 'en',
            default => null,
        };
    }

    public function mapCountryToLocale(?string $countryCode): ?string
    {
        if (! $countryCode) {
            return null;
        }

        $countryCode = strtoupper(trim($countryCode));

        $mapping = config('app.country_locale_map', [
            'pt' => ['BR', 'PT', 'AO', 'MZ', 'CV', 'GW', 'ST', 'TL'],
            'es' => ['AR', 'BO', 'CL', 'CO', 'CR', 'CU', 'DO', 'EC', 'GT', 'HN', 'MX', 'NI', 'PA', 'PY', 'PE', 'PR', 'ES', 'UY', 'VE'],
            'en' => ['US', 'GB', 'AU', 'CA', 'NZ', 'IE', 'SG', 'ZA', 'NG', 'JM'],
        ]);

        foreach ($mapping as $locale => $countries) {
            if (in_array($countryCode, $countries, true)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Detecta a localização do visitante pelo IP real.
     * Agora passa o IP do visitante para o serviço de geolocalização.
     */
    public function geolocateByIp(Request $request): array
    {
        $visitorIp = $request->ip();

        if (! $visitorIp || $visitorIp === '127.0.0.1' || $visitorIp === '::1') {
            // IP local não pode ser geolocalizado
            \Log::debug('VisitorLanguageService: IP local detected, skipping geolocation', ['ip' => $visitorIp]);
            return ['locale' => null, 'country_code' => null];
        }

        // Tenta primeiro com ipapi.co passando o IP do visitante
        $result = $this->geolocateWithIpApiCo($visitorIp);

        if ($result['locale'] !== null) {
            return $result;
        }

        // Fallback: tenta ip-api.com (gratuito, sem necessidade de API key)
        $result = $this->geolocateWithIpApi($visitorIp);

        if ($result['locale'] !== null) {
            return $result;
        }

        \Log::warning('VisitorLanguageService: All geolocation services failed', ['ip' => $visitorIp]);

        return ['locale' => null, 'country_code' => null];
    }

    protected function geolocateWithIpApiCo(string $ip): array
    {
        try {
            $response = $this->httpClient()->get("https://ipapi.co/{$ip}/json/");
        } catch (ConnectionException $exception) {
            \Log::warning('VisitorLanguageService: ipapi.co connection failed', ['error' => $exception->getMessage()]);
            return ['locale' => null, 'country_code' => null];
        }

        if (! $response->successful()) {
            \Log::warning('VisitorLanguageService: ipapi.co returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['locale' => null, 'country_code' => null];
        }

        $countryCode = $response->json('country_code');
        $locale = $this->mapCountryToLocale($countryCode);

        \Log::debug('VisitorLanguageService: ipapi.co result', [
            'ip' => $ip,
            'country_code' => $countryCode,
            'locale' => $locale,
        ]);

        return [
            'locale' => $locale,
            'country_code' => $countryCode,
        ];
    }

    protected function geolocateWithIpApi(string $ip): array
    {
        try {
            $response = $this->httpClient()->get("https://ip-api.com/json/{$ip}?fields=status,countryCode");
        } catch (ConnectionException $exception) {
            \Log::warning('VisitorLanguageService: ip-api.com connection failed', ['error' => $exception->getMessage()]);
            return ['locale' => null, 'country_code' => null];
        }

        if (! $response->successful() || $response->json('status') !== 'success') {
            \Log::warning('VisitorLanguageService: ip-api.com returned error', [
                'status' => $response->json('status'),
                'body' => $response->body(),
            ]);
            return ['locale' => null, 'country_code' => null];
        }

        $countryCode = $response->json('countryCode');
        $locale = $this->mapCountryToLocale($countryCode);

        \Log::debug('VisitorLanguageService: ip-api.com result', [
            'ip' => $ip,
            'country_code' => $countryCode,
            'locale' => $locale,
        ]);

        return [
            'locale' => $locale,
            'country_code' => $countryCode,
        ];
    }

    protected function httpClient(): PendingRequest
    {
        return Http::withUserAgent('portfolio-visitor-language/1.0')
            ->timeout(5)
            ->retry(1, 100);
    }
}