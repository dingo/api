<?php

namespace Dingo\Api\Providers;

use Dingo\Api\Exception\Handler;
use Dingo\Api\Events\RevisingHandler;
use Dingo\Api\Events\ExceptionHandler;
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
        $this->app['events']->listen('router.exception', 'Dingo\Api\Events\ExceptionHandler');
        $this->app['events']->listen('router.matched', 'Dingo\Api\Events\RevisingHandler');
        
        $this->app['router']->filter('auth.api', 'Dingo\Api\Http\Filter\AuthFilter');
        $this->app['router']->filter('api.throttle', 'Dingo\Api\Http\Filter\RateLimitFilter');
	}

    /**
     * Register bindings for the service provider.
     * 
     * @return void
     */
	public function register()
	{
		$this->app->bind('Dingo\Api\Events\ExceptionHandler', function ($app) {
            return new ExceptionHandler(new Handler);
        });

        $this->app->bind('Dingo\Api\Events\RevisingHandler', function ($app) {
            return new RevisingHandler($app['router'], new ControllerReviser($app));
        });
	}
}
