<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para sanitização de entrada.
 * 
 * Aplica trim e limpeza básica em todos os dados recebidos
 * antes de qualquer processamento no backend.
 * 
 * Segue a regra de Sanitização de Entrada do .clinerules:
 * "Aplicar rigorosamente mecanismos de limpeza e trim em todos
 *  os dados recebidos no backend antes de qualquer processamento."
 */
class SanitizeInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get')) {
            return $next($request);
        }

        $request->merge(
            $this->sanitize($request->all())
        );

        return $next($request);
    }

    /**
     * Recursively sanitize input data.
     * - Trims whitespace from strings
     * - Removes empty strings (converts to null)
     * - Strips invisible control characters
     */
    private function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            $data = trim($data);
            // Remove zero-width characters and control chars (except newlines/tabs)
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            // Convert empty strings to null for cleaner DB storage
            return $data === '' ? null : $data;
        }

        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        return $data;
    }
}
