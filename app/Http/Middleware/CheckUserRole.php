<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce role-based access control.
 * - 'patient': Only Patient role can access
 * - 'staff': Only Admin and Operational can access
 * - 'admin': Only Admin can access
 */
class CheckUserRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user || !$user->role instanceof UserRole) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $allowed = match ($role) {
            'patient' => $user->role->isPatient(),
            'staff' => $user->role->isStaff(),
            'admin' => $user->role === UserRole::Admin,
            default => false,
        };

        if (!$allowed) {
            return response()->json(['message' => 'Acesso restrito a esta área.'], 403);
        }

        return $next($request);
    }
}
