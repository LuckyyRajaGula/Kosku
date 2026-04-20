<?php

use App\Http\Controllers\Api\ManagerAccountController;
use App\Http\Controllers\Api\JwtAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [JwtAuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [JwtAuthController::class, 'me']);
        Route::post('/refresh', [JwtAuthController::class, 'refresh']);
        Route::post('/logout', [JwtAuthController::class, 'logout']);

        Route::prefix('/owner/pengelola')->group(function (): void {
            Route::get('/', [ManagerAccountController::class, 'index']);
            Route::post('/', [ManagerAccountController::class, 'store']);
            Route::put('/{idUser}', [ManagerAccountController::class, 'update']);
            Route::patch('/{idUser}/status', [ManagerAccountController::class, 'updateStatus']);
        });
    });
});
