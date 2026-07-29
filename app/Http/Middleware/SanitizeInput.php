<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to sanitize all input data for non-GET requests.
 * 
 * Aplica trim() e strip_tags() em todas as strings recebidas para remover
 * espaços desnecessários e prevenir ataques de XSS por injeção de scripts.
 * 
 * A sanitização de tags HTML é obrigatória para conformidade com a política
 * de segurança do sistema (clinerules §4 - Higienização de Entradas).
 */
class SanitizeInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $request->merge(
            $this->sanitize($request->all())
        );

        return $next($request);
    }

    /**
     * Recursively sanitize input data.
     * Aplica trim() para remover espaços e strip_tags() para remover
     * tags HTML/JS maliciosas antes de qualquer processamento.
     */
    private function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            // strip_tags() primeiro para remover scripts/tags HTML,
            // depois trim() para remover espaços residuais
            return $this->trimNullToNull(strip_tags(trim($data)));
        }

        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        return $data;
    }

    /**
     * Converte string vazia resultante do trim em null.
     * Evita persistir strings vazias no banco de dados.
     */
    private function trimNullToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
