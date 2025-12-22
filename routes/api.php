<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/profile', [App\Http\Controllers\AssetController::class, 'index']);
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\OrderController::class, 'destroy']);
});
