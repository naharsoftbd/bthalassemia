<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Products\ProductImportController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('import/template', [ProductImportController::class, 'downloadTemplateSimple'])->name('products.import.template');

require __DIR__.'/auth.php';
