<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Routing\ControllerDispatcher;
use Dingo\Api\Routing\Router;
use Dingo\Api\Routing\UrlGenerator;
use Illuminate\Routing\RoutingServiceProvider as ServiceProvider;
use Illuminate\Support\Collection;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->replaceBoundRouter();
        $this->replaceBoundUrlGenerator();
    }

    /**
     * Replace the bound router.
     *
     * @return void
     */
    protected function replaceBoundRouter()
    {
        $routes = $this->app['router']->getRoutes();

        $this->app->bindShared('router', function ($app) use ($routes) {
            $router = new Router($app['events'], $app['api.properties'], $app);

            if ($app['env'] == 'testing') {
                $router->disableFilters();
            }

            $router->setControllerDispatcher(new ControllerDispatcher($router, $app));
            $router->setConditionalRequest($app['config']->get('api::conditional_request'));
            $router->setStrict($app['config']->get('api::strict'));
            $router->addExistingRoutes($routes);

            return $router;
        });
    }

    /**
     * Replace the bound URL generator.
     *
     * @return void
     */
    protected function replaceBoundUrlGenerator()
    {
        $this->app->bindShared('url', function ($app) {
            $routes = Collection::make($app['router']->getRoutes())->merge($app['router']->getApiGroups()->getRoutes());

            return new UrlGenerator($routes, $app->rebinding('request', function ($app, $request) {
                $app['url']->setRequest($request);
            }));
        });
    }
}
