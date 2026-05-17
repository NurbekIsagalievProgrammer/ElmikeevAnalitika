<?php

namespace App\Providers;

use App\Services\WbApiClient;
use App\Services\WbSyncService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WbApiClient::class, fn () => WbApiClient::fromConfig());
        $this->app->singleton(WbSyncService::class, fn ($app) => new WbSyncService($app->make(WbApiClient::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
