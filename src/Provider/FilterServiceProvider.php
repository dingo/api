<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Http\Filter\AuthFilter;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Http\Filter\RateLimitFilter;

class FilterServiceProvider extends ServiceProvider
{
    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Dingo\Api\Http\Filter\AuthFilter', function ($app) {
            return new AuthFilter($app['router'], $app['events'], $app['api.auth']);
        });

        $this->app->bind('Dingo\Api\Http\Filter\RateLimitFilter', function ($app) {
            return new RateLimitFilter($app['router'], $app['api.limiter']);
        });
    }
}
