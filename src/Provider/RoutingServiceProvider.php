<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Routing\Router;
use Dingo\Api\Routing\UrlGenerator;
use Dingo\Api\Contract\Routing\Adapter;
use Dingo\Api\Routing\ResourceRegistrar;
use Dingo\Api\Contract\Debug\ExceptionHandler;

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
                $app[Adapter::class],
                $app[ExceptionHandler::class],
                $app,
                $this->config('domain'),
                $this->config('prefix')
            );

            $router->setConditionalRequest($this->config('conditionalRequest'));

            return $router;
        });

        $this->app->singleton(ResourceRegistrar::class, function ($app) {
            return new ResourceRegistrar($app[Router::class]);
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

            $url->setRouteCollections($app[Router::class]->getRoutes());

            return $url;
        });
    }
}
