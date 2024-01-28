<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\IApplePayService;
use App\Services\ApplePayServiceImpl;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IApplePayService::class, ApplePayServiceImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
