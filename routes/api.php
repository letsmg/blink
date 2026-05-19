<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\UnavailabilityPeriodController;
use App\Http\Middleware\CheckUserRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All communication is done via RESTful APIs.
| This infrastructure serves both the Vue.js SPA and future Flutter app.
|
*/

// =============================================
// Public Routes (No authentication required)
// =============================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// =============================================
// Authenticated Routes
// =============================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/accept-terms', [AuthController::class, 'acceptTerms']);

    // =============================================
    // Patient Area (Patient role only)
    // =============================================
    Route::middleware(CheckUserRole::class . ':patient')
        ->prefix('patient')
        ->group(function () {
            // Patient-specific routes will be added here
        });

    // =============================================
    // Staff Area (Admin + Operational)
    // =============================================
    Route::middleware(CheckUserRole::class . ':staff')
        ->prefix('staff')
        ->group(function () {

            // Messages (internal communication)
            Route::prefix('messages')->group(function () {
                Route::get('/', [MessageController::class, 'index']);
                Route::post('/', [MessageController::class, 'send']);
                Route::get('/unread-count', [MessageController::class, 'unreadCount']);
                Route::patch('/{message}/read', [MessageController::class, 'markAsRead']);
            });

            // Patients management
            Route::get('/patients', [PatientController::class, 'index']);
            Route::get('/patients/{patient}', [PatientController::class, 'show']);
            Route::put('/patients/{patient}', [PatientController::class, 'update']);

            // Professional unavailability periods
            Route::prefix('professionals/{professional}/unavailability')->group(function () {
                Route::get('/', [UnavailabilityPeriodController::class, 'index']);
                Route::get('/future', [UnavailabilityPeriodController::class, 'future']);
                Route::post('/', [UnavailabilityPeriodController::class, 'store']);
                Route::put('/{period}', [UnavailabilityPeriodController::class, 'update']);
                Route::delete('/{period}', [UnavailabilityPeriodController::class, 'destroy']);
            });
        });
});
