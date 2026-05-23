<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use Illuminate\Http\JsonResponse;

class ProfessionalController extends Controller
{
    /**
     * List all active professionals (Staff only).
     * Used by Unavailability.vue and Dashboard.vue.
     */
    public function index(): JsonResponse
    {
        $professionals = Professional::select('id', 'full_name', 'specialty', 'is_active')
            ->orderBy('full_name')
            ->get();

        return response()->json(['data' => $professionals]);
    }

    /**
     * Get locations linked to a specific professional.
     * Used for dynamic location selection when scheduling appointments.
     * Filtra apenas os locais onde o profissional atende via tabela pivô.
     */
    public function locations(Professional $professional): JsonResponse
    {
        $locations = $professional->locations()
            ->select('locations.id', 'locations.name', 'locations.address', 'locations.city')
            ->orderBy('locations.name')
            ->get();

        return response()->json(['data' => $locations]);
    }
}
