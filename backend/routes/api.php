<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/v1/health', HealthController::class);
Route::get('/v1/health/ready', [HealthController::class, 'ready']);
Route::post('/v1/auth/register', [AuthController::class, 'register']);