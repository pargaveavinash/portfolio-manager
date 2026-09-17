<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CashTransactionController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\HoldingController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\PortfolioSummaryController;
use App\Http\Controllers\Api\V1\SipPlanController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Http\Request;
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

    Route::post(
        '/v1/portfolios/{portfolio}/holdings/{holding}/transactions',
        [TransactionController::class, 'store']
    );
    Route::get(
        '/v1/portfolios/{portfolio}/transactions',
        [TransactionController::class, 'index']
    );

    Route::get(
        '/v1/portfolios/{portfolio}/transactions/{transaction}',
        [TransactionController::class, 'show']
    );

    Route::put(
        '/v1/portfolios/{portfolio}/transactions/{transaction}',
        [TransactionController::class, 'update']
    );

    Route::delete(
        '/v1/portfolios/{portfolio}/transactions/{transaction}',
        [TransactionController::class, 'destroy']
    );

    Route::get(
        '/v1/portfolios/{portfolio}/rebalancing',
        [PortfolioController::class, 'rebalancing']
    );

    Route::get(
        '/v1/portfolios/{portfolio}/summary',
        [PortfolioSummaryController::class, 'show']
    );

    Route::get(
        '/v1/portfolios/{portfolio}/sip-plan',
        [SipPlanController::class, 'show']
    );

    Route::post(
        '/v1/portfolios/{portfolio}/cash-transactions',
        [CashTransactionController::class, 'store']
    );
});

Route::get('/v1/health', HealthController::class);
Route::get('/v1/health/ready', [HealthController::class, 'ready']);