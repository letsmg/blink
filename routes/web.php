<?php

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
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
