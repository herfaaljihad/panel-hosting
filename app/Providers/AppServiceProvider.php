<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MonitoringService;
use App\Services\CacheService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register monitoring service as singleton
        $this->app->singleton(MonitoringService::class, function ($app) {
            return new MonitoringService();
        });

        // Register cache service as singleton  
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Boot services
    }
}
