<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\HoldingController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/v1/auth/register', [AuthController::class, 'register']);
Route::post('/v1/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/auth/me', [AuthController::class, 'me']);
    Route::post('/v1/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('v1/portfolios', PortfolioController::class);
    Route::post(
        '/v1/portfolios/{portfolio}/holdings',
        [HoldingController::class, 'store']
    );
    Route::get(
        '/v1/portfolios/{portfolio}/holdings',
        [HoldingController::class, 'index']
    );

    Route::get(
        '/v1/portfolios/{portfolio}/holdings/{holding}',
        [HoldingController::class, 'show']
    );

    Route::put(
        '/v1/portfolios/{portfolio}/holdings/{holding}',
        [HoldingController::class, 'update']
    );

    Route::delete(
        '/v1/portfolios/{portfolio}/holdings/{holding}',
        [HoldingController::class, 'destroy']
    );
});

Route::get('/v1/health', HealthController::class);
Route::get('/v1/health/ready', [HealthController::class, 'ready']);