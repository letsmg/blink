<?php

use App\Http\Controllers\SpaController;
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

// Catch-all route for the SPA - renders the Vue app for all non-API routes
// Using a controller instead of a closure to allow route:cache to work in production
Route::get('/{any?}', [SpaController::class, 'index'])->where('any', '.*');
