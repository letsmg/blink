<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify that the user has accepted the terms of use.
 *
 * Bloqueia o acesso a áreas restritas caso o usuário logado ainda não
 * tenha aceitado os termos de uso e política de privacidade.
 * O aceite é verificado no banco de dados (users.terms_accepted),
 * não apenas em cookies ou sessão.
 */
class CheckTermsAccepted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasAcceptedTerms()) {
            return response()->json([
                'message' => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para acessar esta funcionalidade.',
                'requires_terms_acceptance' => true,
            ], 403);
        }

        return $next($request);
    }
}
