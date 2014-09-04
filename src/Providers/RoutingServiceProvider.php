<?php

namespace Dingo\Api\Providers;

use Dingo\Api\Routing\Router;
use Dingo\Api\Routing\ControllerDispatcher;
use Illuminate\Routing\RoutingServiceProvider as ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     * 
     * @return void
     */
	public function boot()
	{
		$this->app->bindShared('router', function ($app) {
            $router = new Router($app['events'], $app);

            if ($app['env'] == 'testing') $router->disableFilters();

            $router->setControllerDispatcher(new ControllerDispatcher($router, $app));

            return $router;
        });
        
        $this->app->booted([$this, 'setRouterDefaults']);

        parent::boot();
	}

    /**
     * Set the default configuration options on the router.
     * 
     * @return void
     */
    public function setRouterDefaults()
    {
        $config = $this->app['config']['api::config'];

        $this->app['router']->setDefaultVersion($config['version']);
        $this->app['router']->setDefaultPrefix($config['prefix']);
        $this->app['router']->setDefaultDomain($config['domain']);
        $this->app['router']->setDefaultFormat($config['default_format']);
        $this->app['router']->setVendor($config['vendor']);
        $this->app['router']->setConditionalRequest($config['conditional_request']);
    }
}
