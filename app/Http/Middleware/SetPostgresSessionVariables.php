<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injeta o ID e o papel (role) do usuário autenticado na sessão do PostgreSQL
 * antes de cada query, viabilizando as políticas de Row Level Security (RLS).
 *
 * As variáveis 'app.current_user_id' e 'app.current_user_role' são definidas
 * via SET LOCAL, portanto seu escopo é limitado à transação/query atual,
 * sendo automaticamente descartadas ao final — sem risco de vazamento entre requests.
 */
class SetPostgresSessionVariables
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $pdo = DB::connection()->getPdo();

            // Protege e formata os valores de forma segura para o SQL do Postgres
            $userId = $pdo->quote((string) $user->id);
            $userRole = $pdo->quote($user->role->value);

            // SET LOCAL garante que o valor só existe durante a transação atual
            DB::statement("SET LOCAL app.current_user_id = {$userId}");
            DB::statement("SET LOCAL app.current_user_role = {$userRole}");
        }

        return $next($request);
    }
}