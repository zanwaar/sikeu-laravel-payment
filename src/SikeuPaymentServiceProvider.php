<?php

namespace Sikeu\LaravelPayment;

use Illuminate\Support\ServiceProvider;

class SikeuPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sikeu.php', 'sikeu'
        );

        $this->app->singleton(\Sikeu\LaravelPayment\Services\SikeuPaymentService::class, function ($app) {
            return new \Sikeu\LaravelPayment\Services\SikeuPaymentService();
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/sikeu.php' => config_path('sikeu.php'),
        ], 'sikeu-config');
    }
}
