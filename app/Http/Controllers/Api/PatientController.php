<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientRepository $patientRepository,
    ) {}

    /**
     * List all patients (Staff only).
     */
    public function index(Request $request): JsonResponse
    {
        $patients = $this->patientRepository->paginate(
            $request->get('per_page', 15)
        );

        return response()->json(['data' => $patients]);
    }

    /**
     * Get a specific patient with their appointments and diagnostics.
     */
    public function show(Patient $patient): JsonResponse
    {
        $patient->load(['appointments', 'diagnostics.professional']);

        return response()->json(['data' => $patient]);
    }

    /**
     * Update patient profile.
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'main_complaint' => ['nullable', 'string', 'max:1000'],
            'street' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'clinical_history' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->patientRepository->update($patient, $validated);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso!',
            'data' => $patient->fresh(),
        ]);
    }
}
