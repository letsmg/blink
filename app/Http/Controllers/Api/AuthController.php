<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPatientRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Register a new patient.
     */
    public function register(RegisterPatientRequest $request): JsonResponse
    {
        $result = $this->authService->registerPatient($request->validated());

        return response()->json([
            'message' => 'Cadastro realizado com sucesso!',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Authenticate user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login realizado com sucesso!',
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['patient', 'professional']);

        return response()->json(['user' => $user]);
    }

    /**
     * Accept terms of use.
     */
    public function acceptTerms(Request $request): JsonResponse
    {
        $user = $this->authService->acceptTerms($request->user());

        return response()->json([
            'message' => 'Termos aceitos com sucesso!',
            'user' => $user,
        ]);
    }
}
