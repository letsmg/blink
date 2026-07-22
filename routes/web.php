<?php
// Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

use App\Http\Controllers\SpaController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\VisitorLanguageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| All SPA routes are handled by Vue Router.
| The catch-all route ensures Vue handles all frontend routes.
|
*/

// Translation routes (public, no auth needed)
Route::prefix('api')->group(function (): void {
    Route::get('/translations', [TranslationController::class, 'index']);
    Route::get('/translations/{locale}', [TranslationController::class, 'show']);
});

// Visitor language preference routes (public, no auth needed)
Route::prefix('api')->group(function (): void {
    Route::get('/visitor-language', [VisitorLanguageController::class, 'show']);
    Route::post('/visitor-language', [VisitorLanguageController::class, 'store']);
    Route::get('/visitor-language/fallback', [VisitorLanguageController::class, 'fallback']);
});

// Catch-all route for the SPA - renders the Vue app for all non-API routes
// Using a controller instead of a closure to allow route:cache to work in production
Route::get('/{any?}', [SpaController::class, 'index'])->where('any', '.*');
