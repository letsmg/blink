<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitorLanguageRequest;
use App\Repositories\VisitorLanguageRepository;
use App\Services\VisitorLanguageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class VisitorLanguageController
{
    public function __construct(
        protected VisitorLanguageRepository $repository,
        protected VisitorLanguageService $service,
    ) {
    }

    public function show(Request $request): Response
    {
        $visitorId = $request->cookie('visitor_id');
        $locale = $this->service->detectLocaleFromAcceptLanguage($request->header('Accept-Language'));
        $payload = [
            'supported_locales' => $this->service->getSupportedLocales(),
            'browser_locale' => $locale,
        ];

        \Log::debug('VisitorLanguageController::show', [
            'visitor_id_received' => $visitorId,
            'cookies' => $request->cookies->all(),
        ]);

        if ($visitorId === null) {
            return response($payload);
        }

        $visitor = $this->repository->findByVisitorId($visitorId);

        if (! $visitor) {
            return response($payload);
        }

        $visitor->first_visit = false;
        $visitor->last_visit_at = now();
        $this->repository->save($visitor);

        return response([
            'visitor_id' => $visitor->visitor_id,
            'preferred_locale' => $visitor->preferred_locale,
            'origin' => $visitor->origin,
            'country_code' => $visitor->country_code,
            'terms_accepted' => $visitor->terms_accepted,
            'privacy_accepted' => $visitor->privacy_accepted,
            'accepted_at' => $visitor->accepted_at,
            'browser_locale' => $locale,
            'first_visit' => $visitor->first_visit,
            'supported_locales' => $this->service->getSupportedLocales(),
        ]);
    }

    public function store(StoreVisitorLanguageRequest $request): Response
    {
        $visitorId = $request->cookie('visitor_id');
        $visitor = $visitorId ? $this->repository->findByVisitorId($visitorId) : null;

        $acceptedAt = null;
        $termsAccepted = $request->boolean('terms_accepted');
        $privacyAccepted = $request->boolean('privacy_accepted');

        if ($termsAccepted || $privacyAccepted) {
            $acceptedAt = now();
        }

        $attributes = [
            'preferred_locale' => $request->input('preferred_locale'),
            'origin' => $request->input('origin'),
            'country_code' => $request->input('country_code'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'terms_accepted' => $termsAccepted,
            'privacy_accepted' => $privacyAccepted,
            'accepted_at' => $acceptedAt,
            'terms_version' => $request->input('terms_version'),
            'privacy_version' => $request->input('privacy_version'),
            'last_visit_at' => now(),
        ];

        if (! $visitor) {
            $visitor = $this->repository->create([
                'visitor_id' => Str::uuid()->toString(),
                'first_visit' => true,
                'first_visit_at' => now(),
                ...$attributes,
            ]);
        } else {
            $visitor->fill($attributes);
            $visitor->first_visit = false;
            $this->repository->save($visitor);
        }

        /**
         * Configura o cookie do visitor_id.
         *
         * IMPORTANTE: 
         * - $secure deve ser false em HTTP local para o cookie ser aceito.
         * - Em produção com HTTPS, deve ser true.
         * - O método request->secure() detecta se a requisição atual é HTTPS.
         */
        $secure = $request->secure();
        
        /**
         * SameSite: se a requisição for cross-site (ex: POST de API),
         * precisamos usar 'none' + secure para que o cookie seja enviado.
         * Mas para requisições same-site (GET normais), 'lax' funciona bem.
         * Aqui mantemos 'lax' para compatibilidade.
         */
        $sameSite = config('session.same_site', 'lax');

        $responseData = [
            'visitor_id' => $visitor->visitor_id,
            'preferred_locale' => $visitor->preferred_locale,
            'origin' => $visitor->origin,
            'country_code' => $visitor->country_code,
            'terms_accepted' => $visitor->terms_accepted,
            'privacy_accepted' => $visitor->privacy_accepted,
            'accepted_at' => $visitor->accepted_at,
            'first_visit' => $visitor->first_visit,
            'supported_locales' => $this->service->getSupportedLocales(),
        ];

        \Log::debug('VisitorLanguageController::store - setting cookie', [
            'visitor_id' => $visitor->visitor_id,
            'secure' => $secure,
            'same_site' => $sameSite,
            'request_secure' => $request->secure(),
            'scheme' => $request->getScheme(),
        ]);

        return response($responseData)
            ->cookie('visitor_id', $visitor->visitor_id, 525600, '/', null, $secure, false, $sameSite);
    }

    public function fallback(Request $request): Response
    {
        $fallback = $this->service->geolocateByIp($request);

        \Log::debug('VisitorLanguageController::fallback', [
            'locale' => $fallback['locale'],
            'country_code' => $fallback['country_code'],
            'ip' => $request->ip(),
        ]);

        return response([
            'locale' => $fallback['locale'],
            'country_code' => $fallback['country_code'],
            'supported_locales' => $this->service->getSupportedLocales(),
        ]);
    }
}