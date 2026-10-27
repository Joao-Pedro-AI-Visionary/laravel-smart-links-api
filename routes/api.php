<?php

use App\Http\Controllers\Api\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::get('/links', [ShortLinkController::class, 'index']);
    Route::post('/links', [ShortLinkController::class, 'store']);
    Route::put('/links/{slug}', [ShortLinkController::class, 'update']);
    Route::get('/links/{slug}/stats', [ShortLinkController::class, 'stats']);
    Route::delete('/links/{slug}', [ShortLinkController::class, 'destroy']);
});
