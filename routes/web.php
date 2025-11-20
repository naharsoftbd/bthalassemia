<?php

use App\Http\Controllers\Api\V1\Products\ProductImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('import/template', [ProductImportController::class, 'downloadTemplateSimple'])->name('products.import.template');

require __DIR__.'/auth.php';
