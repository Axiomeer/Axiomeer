<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SemanticKernelService::class, function ($app) {
            $sk = new \App\Services\SemanticKernelService($app->make(\App\Services\Azure\AzureOpenAIService::class));
            $sk->registerAxiomeerSkills();
            return $sk;
        });

        $this->app->singleton(\App\Services\Azure\KeyVaultService::class, function () {
            return new \App\Services\Azure\KeyVaultService();
        });

        $this->app->singleton(\App\Services\Azure\ServiceBusService::class, function () {
            return new \App\Services\Azure\ServiceBusService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Use getHost() and isProduction() with correct camelCase
        $istunnel = str_contains(request()->getHost(), 'trycloudflare.com') || str_contains(request()->getHost(), 'ngrok-free.app');
        $isproduction = app()->isProduction();
        $islocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        if (($istunnel || $isproduction) && !$islocalhost) {
            URL::forceScheme('https');
        }
    }

}
