<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for generating patient reports.
 * 
 * Intenção: Fornecer endpoints para relatórios de pacientes com filtros
 * por profissional, ordem alfabética ou por idade.
 */
class ReportsController extends Controller
{
    /**
     * Generate patient report with filters.
     * 
     * Query params:
     * - professional_id: int (optional) - Filter by professional
     * - order: string (optional) - 'name' (default) or 'age'
     * - direction: string (optional) - 'asc' (default) or 'desc'
     */
    public function patients(Request $request): JsonResponse
    {
        $query = Patient::with('user');

        // Filtro por profissional (via appointments)
        if ($request->filled('professional_id')) {
            $professionalId = $request->professional_id;
            $query->whereHas('appointments', function ($q) use ($professionalId) {
                $q->where('professional_id', $professionalId);
            });
        }

        // Ordenação
        $order = $request->get('order', 'name');
        $direction = $request->get('direction', 'asc');

        if ($order === 'age') {
            // Ordenar por data de nascimento (mais novo primeiro = desc, mais velho primeiro = asc)
            $query->orderBy('date_of_birth', $direction === 'asc' ? 'desc' : 'asc');
        } else {
            // Ordenar por nome — full_name foi removido da tabela patients, usa join com users.display_name
            $query->join('users', 'patients.user_id', '=', 'users.id')
                ->select('patients.*')
                ->orderBy('users.display_name', $direction);
        }

        $patients = $query->paginate($request->get('per_page', 50));

        return response()->json(['data' => $patients]);
    }

    /**
     * List professionals for the report filter.
     */
    public function professionals(): JsonResponse
    {
        $professionals = Professional::select('id', 'full_name', 'specialty')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return response()->json(['data' => $professionals]);
    }
}
