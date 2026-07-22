<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce role-based access control.
 * - 'patient': Only Patient role can access
 * - 'staff': Admin, AdminOperational and Professional can access
 * - 'admin': Admin and AdminOperational can access
 * - 'super-admin': Only Admin (Geral) can access
 * - 'professional': Only Professional can access
 */
class CheckUserRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role instanceof UserRole) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        // Bloqueio de sistema — apenas Admin Geral pode reativar
        if ($user->is_blocked ?? false) {
            return response()->json(['message' => 'Conta bloqueada. Entre em contato com o administrador.'], 403);
        }

        $allowed = match ($role) {
            'patient'     => $user->role->isPatient(),
            'staff'       => $user->role->isStaff(),
            'admin'       => $user->role->isAdmin(),
            'super-admin' => $user->role->isSuperAdmin(),
            'professional' => $user->role->isProfessional(),
            default       => false,
        };

        if (! $allowed) {
            return response()->json(['message' => 'Acesso restrito a esta área.'], 403);
        }

        return $next($request);
    }
}