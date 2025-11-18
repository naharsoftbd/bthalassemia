<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Products\ProductRepositoryInterface;
use App\Products\Repositories\Products\ProductRepository;
use App\Repositories\Auth\AuthRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
