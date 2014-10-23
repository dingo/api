<?php

namespace Dingo\Api\Provider;

use Dingo\Api\Config;
use Dingo\Api\Routing\Router;
use Illuminate\Support\Collection;
use Dingo\Api\Routing\UrlGenerator;
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
        $this->app->bindShared('router', function ($app) {
            $router = new Router($app['events'], $app['api.routing.config'], $app);

            if ($app['env'] == 'testing') {
                $router->disableFilters();
            }

            $router->setControllerDispatcher(new ControllerDispatcher($router, $app));
            $router->setConditionalRequest($app['config']->get('api::conditional_request'));
            $router->setStrict($app['config']->get('api::strict'));

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
            $routes = Collection::make($app['router']->getRoutes())->merge($app['router']->getApiRoutes());

            return new UrlGenerator($routes, $app->rebinding('request', function ($app, $request) {
                $app['url']->setRequest($request);
            }));
        });
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->bindShared('api.routing.config', function ($app) {
            $config = $app['config']->get('api::config');

            return new Config(
                $config['version'],
                $config['prefix'],
                $config['domain'],
                $config['vendor'],
                $config['default_format']
            );
        });
    }
}
