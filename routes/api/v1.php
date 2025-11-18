<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Products\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('variants', ProductVariantController::class);

    Route::post('variants/{variant}/adjust-stock', [ProductVariantController::class, 'adjustStock']);
});

Route::middleware(['jwt.verify', 'jwt.refresh'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
});
