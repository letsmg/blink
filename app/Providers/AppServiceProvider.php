<?php

// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Configura limitadores de requisições agressivos para mitigar
     * ataques de força bruta e timing attacks nas rotas de autenticação,
     * registro e recuperação de senha (clinerules §4 - Rate Limiting).
     */
    public function boot(): void
    {
        // Rate limiter para rotas de autenticação (login, register, forgot-password)
        // 5 tentativas por minuto por IP — extremamente restritivo para mitigar brute-force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip() . '|' . $request->input('email', '')
            );
        });

        // Rate limiter para rotas gerais da API autenticada
        // 60 requisições por minuto por usuário
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}
