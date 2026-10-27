<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if ($request->wantsJson()) {
        return response()->json([
            'service' => 'laravel-smart-links-api',
            'version' => '1.0.0',
            'status' => 'healthy',
        ]);
    }

    return view('welcome');
});

Route::get('/r/{slug}', RedirectController::class)->middleware('throttle:120,1');
