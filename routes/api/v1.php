<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Products\ProductVariantController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('jwt.verify')->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware(['jwt.verify', 'jwt.refresh'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('variants', ProductVariantController::class);

    Route::post('variants/{variant}/adjust-stock', [ProductVariantController::class, 'adjustStock']);

    // Order routes - FIXED ORDER
    Route::prefix('orders')->group(function () {
        // POST routes FIRST (before parameter routes)
        Route::post('/', [OrderController::class, 'store'])->name('orders.store');
        
        // Specific POST actions (before parameter routes)
        Route::post('{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('{order}/force-cancel', [OrderController::class, 'forceCancel'])
            ->middleware('permission:manage orders')
            ->name('orders.force-cancel');
        
        // GET routes
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        
        // Parameter routes LAST
        Route::get('{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    });
});
