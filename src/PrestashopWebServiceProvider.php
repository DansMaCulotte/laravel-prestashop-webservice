<?php

namespace DansMaCulotte\PrestashopWebService;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PrestashopWebServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/prestashop-webservice.php', 'prestashop-webservice');

        $this->publishes([
            __DIR__.'/../config/prestashop-webservice.php' => config_path('prestashop-webservice.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PrestashopWebService::class, function () {
            return new PrestashopWebService(
                config('prestashop-webservice.url'),
                config('prestashop-webservice.token'),
                config('prestashop-webservice.debug')
            );
        });

        $this->app->alias(PrestashopWebService::class, 'prestashop-webservice');
    }

    public function provides()
    {
        return [PrestashopWebService::class];
    }
}
