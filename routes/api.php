<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\UnavailabilityPeriodController;
use App\Http\Controllers\Api\UserController;
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

// Terms acceptance (public - for non-authenticated visitors)
Route::post('/accept-terms', [TermController::class, 'accept']);
Route::get('/check-terms', [TermController::class, 'check']);

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
    Route::middleware(CheckUserRole::class.':patient')
        ->prefix('patient')
        ->group(function () {
            // List professionals for messaging
            Route::get('/professionals', [PatientController::class, 'professionals']);

            // Send message to a professional
            Route::post('/messages', [PatientController::class, 'sendMessage']);

            // Profile management
            Route::get('/profile', [PatientController::class, 'profile']);
            Route::put('/profile', [PatientController::class, 'updateProfile']);

            // Account deactivation request
            Route::post('/deactivate-request', [PatientController::class, 'deactivateRequest']);
        });

    // =============================================
    // Staff Area (Admin + Operational)
    // =============================================
    Route::middleware(CheckUserRole::class.':staff')
        ->prefix('staff')
        ->group(function () {

            // Messages (internal communication)
            Route::prefix('messages')->group(function () {
                Route::get('/', [MessageController::class, 'index']);
                Route::post('/', [MessageController::class, 'send']);
                Route::get('/unread-count', [MessageController::class, 'unreadCount']);
                Route::patch('/{message}/read', [MessageController::class, 'markAsRead']);
                Route::patch('/{message}/toggle-read', [MessageController::class, 'toggleRead']);

            });

            // Patients management
            Route::get('/patients', [PatientController::class, 'index']);
            Route::get('/patients/{patient}', [PatientController::class, 'show']);
            Route::put('/patients/{patient}', [PatientController::class, 'update']);

            // Users list (for message recipient selection)
            Route::get('/users', [UserController::class, 'index']);

            // Appointments (agenda)
            Route::prefix('appointments')->group(function () {
                Route::get('/', [AppointmentController::class, 'index']);
                Route::post('/', [AppointmentController::class, 'store']);
                Route::get('/pending-count', [AppointmentController::class, 'pendingCount']);
                Route::get('/all-unavailable-dates', [AppointmentController::class, 'allUnavailableDates']);
                Route::get('/search/patients', [AppointmentController::class, 'searchPatients']);
                Route::get('/search/professionals', [AppointmentController::class, 'searchProfessionals']);
                Route::get('/professionals/{professional}/unavailable-dates', [AppointmentController::class, 'unavailableDates']);
                Route::get('/{appointment}', [AppointmentController::class, 'show']);
                Route::put('/{appointment}', [AppointmentController::class, 'update']);
                Route::delete('/{appointment}', [AppointmentController::class, 'destroy']);
            });

            // Professionals list
            Route::get('/professionals', [ProfessionalController::class, 'index']);

            // Professional locations (dynamic filter for appointment scheduling)
            Route::get('/professionals/{professional}/locations', [ProfessionalController::class, 'locations']);

            // Professional unavailability periods
            Route::prefix('professionals/{professional}/unavailability')->group(function () {
                Route::get('/', [UnavailabilityPeriodController::class, 'index']);
                Route::get('/future', [UnavailabilityPeriodController::class, 'future']);
                Route::post('/', [UnavailabilityPeriodController::class, 'store']);
                Route::put('/{period}', [UnavailabilityPeriodController::class, 'update']);
                Route::delete('/{period}', [UnavailabilityPeriodController::class, 'destroy']);
            });

            // Reports
            Route::prefix('reports')->group(function () {
                Route::get('/patients', [ReportsController::class, 'patients']);
                Route::get('/professionals', [ReportsController::class, 'professionals']);
            });
        });

});
