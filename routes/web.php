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

Route::get('/', function () {
    return response()->json([
        'app' => 'LaraHealth API',
        'status' => 'online',
        'version' => '1.0.0',
        'environment' => app()->environment(),
        'timestamp' => now()->toIso8601String()
    ]);
});