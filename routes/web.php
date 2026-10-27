<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'laravel-smart-links-api',
        'version' => '1.0.0',
        'status' => 'healthy',
    ]);
});

Route::get('/r/{slug}', RedirectController::class)->middleware('throttle:120,1');
