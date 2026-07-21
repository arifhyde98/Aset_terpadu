<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        if (str_contains(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register Observers
        \App\Models\Vehicle::observe(\App\Observers\VehicleObserver::class);
        \App\Models\Opd::observe(\App\Observers\OpdObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
    }
}
