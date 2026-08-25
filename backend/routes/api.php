<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/v1/auth/register', [AuthController::class, 'register']);
Route::post('/v1/auth/login', [AuthController::class, 'login']);

Route::get('/v1/auth/me', [AuthController::class, 'me'])
    ->middleware('auth:sanctum');
Route::post('/v1/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::get('/v1/health', HealthController::class);
Route::get('/v1/health/ready', [HealthController::class, 'ready']);