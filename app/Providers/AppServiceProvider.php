<?php

namespace App\Providers;

use App\Services\HealthCheckService;
use App\Support\SystemStatusChecker;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(HealthCheckService::class, function ($app) {
            return new HealthCheckService($app['cache.store'], $app['queue']);
        });

        $this->app->singleton(SystemStatusChecker::class, function ($app) {
            return new SystemStatusChecker($app->make(HealthCheckService::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer([
            'layouts.navbars.auth.sidebar',
            'layouts.navbars.auth.sidebar-rtl',
        ], function ($view) {
            $view->with('systemStatus', app(SystemStatusChecker::class)->status());
        });
    }
}
