<?php

use App\Http\Middleware\CheckTermsAccepted;
use App\Http\Middleware\CheckTokenExpiration;
use App\Http\Middleware\CheckUserRole;
use App\Http\Middleware\InjectTokenFromCookie;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SetTokenCookie;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases
        $middleware->alias([
            'role' => CheckUserRole::class,
            'terms.accepted' => CheckTermsAccepted::class,
        ]);

        // Inject token from HttpOnly cookie into Authorization header
        // PRECISA ser executado ANTES do Sanctum para que o token do cookie
        // seja injetado no header antes da autenticação
        $middleware->prependToGroup('api', [
            InjectTokenFromCookie::class,
        ]);

        // Check token expiration for authenticated routes
        $middleware->appendToGroup('api', [
            CheckTokenExpiration::class,
        ]);

        // Global sanitization middleware for all non-GET requests
        $middleware->prependToGroup('api', [
            SanitizeInput::class,
        ]);

        // API middleware group with Sanctum
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // Set token as HttpOnly cookie on login/register responses
        // Executado por último, após a resposta ser gerada
        $middleware->appendToGroup('api', [
            SetTokenCookie::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle unauthenticated requests for API routes
        // Retorna JSON 401 em vez de tentar redirecionar para route('login')
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Não autenticado. Token ausente, inválido ou expirado.',
                ], 401);
            }

            // Para rotas web, redireciona para o login da SPA
            return redirect()->to('/login');
        });
    })->create();
