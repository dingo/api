<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Routing\Router;
use Dingo\Api\Routing\UrlGenerator;
use Dingo\Api\Routing\ResourceRegistrar;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter();

        $this->registerUrlGenerator();
    }

    /**
     * Register the router.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('api.router', function ($app) {
            $router = new Router(
                $app['Dingo\Api\Contract\Routing\Adapter'],
                $app['Dingo\Api\Contract\Debug\ExceptionHandler'],
                $app,
                $this->config('domain'),
                $this->config('prefix')
            );

            $router->setConditionalRequest($this->config('conditionalRequest'));

            return $router;
        });

        $this->app->singleton('Dingo\Api\Routing\ResourceRegistrar', function ($app) {
            return new ResourceRegistrar($app['Dingo\Api\Routing\Router']);
        });
    }

    /**
     * Register the URL generator.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app->singleton('api.url', function ($app) {
            $url = new UrlGenerator($app['request']);

            $url->setRouteCollections($app['Dingo\Api\Routing\Router']->getRoutes());

            return $url;
        });
    }
}
