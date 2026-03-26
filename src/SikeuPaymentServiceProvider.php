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

        // Publish controllers
        $this->publishes([
            __DIR__.'/../app/Http/Controllers/PaymentController.php' => app_path('Http/Controllers/PaymentController.php'),
        ], 'sikeu-controllers');

        // Publish requests
        $this->publishes([
            __DIR__.'/../app/Http/Requests/CreatePaymentRequest.php' => app_path('Http/Requests/CreatePaymentRequest.php'),
        ], 'sikeu-requests');

        // Publish exceptions
        $this->publishes([
            __DIR__.'/../app/Exceptions/SikeuPaymentException.php' => app_path('Exceptions/SikeuPaymentException.php'),
        ], 'sikeu-exceptions');

        // Publish jobs
        $this->publishes([
            __DIR__.'/../app/Jobs/CreatePaymentJob.php' => app_path('Jobs/CreatePaymentJob.php'),
        ], 'sikeu-jobs');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
