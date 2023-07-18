<?php

namespace App\Providers;

use App\Services\ProductivMDService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use App\Providers\Application;

class ProductivMDServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProductivMDService::class, function ($app) {
            return new ProductivMDService();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [ProductivMDService::class];
    }
}
