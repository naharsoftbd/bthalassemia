<?php

use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Products\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
Route::apiResource('variants', ProductVariantController::class);

Route::post('variants/{variant}/adjust-stock', [ProductVariantController::class, 'adjustStock']);
