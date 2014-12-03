<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Event\RevisingHandler;
use Dingo\Api\Event\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Routing\ControllerReviser;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen('router.exception', 'Dingo\Api\Event\ExceptionHandler');
        $this->app['events']->listen('router.matched', 'Dingo\Api\Event\RevisingHandler');

        $this->app['router']->filter('api.auth', 'Dingo\Api\Http\Filter\AuthFilter');
        $this->app['router']->filter('api.throttle', 'Dingo\Api\Http\Filter\RateLimitFilter');
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Dingo\Api\Event\ExceptionHandler', function ($app) {
            return new ExceptionHandler($app['api.exception'], $app['config']->get('api::debug'));
        });

        $this->app->bind('Dingo\Api\Event\RevisingHandler', function ($app) {
            return new RevisingHandler($app['router'], new ControllerReviser($app));
        });
    }
}
