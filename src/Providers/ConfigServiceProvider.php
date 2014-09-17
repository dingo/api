<?php

namespace Dingo\Api\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('dingo/api', 'api', __DIR__.'/../');
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
