<?php

namespace App\Http\Middleware;

use App\Repositories\VisitorLanguageRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetVisitorLocale
{
    public function __construct(protected VisitorLanguageRepository $repository)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($visitorId = $request->cookie('visitor_id')) {
            $visitor = $this->repository->findByVisitorId($visitorId);

            if ($visitor && in_array($visitor->preferred_locale, config('app.supported_locales', ['pt', 'en', 'es']), true)) {
                app()->setLocale($visitor->preferred_locale);
            }
        }

        return $next($request);
    }
}