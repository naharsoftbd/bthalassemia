<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Invoices\InvoiceController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Products\ProductImportController;
use App\Http\Controllers\Api\V1\Products\ProductVariantController;
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

        // Vendor-specific cancellation (cancels only vendor's items)
        Route::post('{order}/cancel-vendor-items', [OrderController::class, 'cancelVendorItems'])
            ->name('orders.cancel-vendor-items');

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

    // Invoice routes
    Route::prefix('orders/{order}/invoice')->group(function () {
        Route::get('download', [InvoiceController::class, 'downloadInvoice'])->name('invoices.download');
        Route::get('vendor', [InvoiceController::class, 'downloadVendorInvoice'])->name('invoices.vendor');
        Route::post('generate', [InvoiceController::class, 'generateInvoice'])->name('invoices.generate');
        Route::get('info', [InvoiceController::class, 'getInvoiceInfo'])->name('invoices.info');
    });

    // Product import routes
    Route::prefix('products')->group(function () {
        Route::post('import', [ProductImportController::class, 'importAsync'])->name('products.import');
        Route::get('import/template', [ProductImportController::class, 'downloadTemplateSimple'])->name('products.import.template');
        Route::get('import/status', [ProductImportController::class, 'getImportStatus'])->name('products.import.status');
    });

});
