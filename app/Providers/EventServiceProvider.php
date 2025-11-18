<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
    }
}
