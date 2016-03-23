<?php

namespace Dingo\Api\Provider;

use ReflectionClass;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Http\Kernel;
use Dingo\Api\Event\RequestWasMatched;
use Illuminate\Routing\ControllerDispatcher;
use Dingo\Api\Routing\Adapter\Laravel as LaravelAdapter;

class LaravelServiceProvider extends DingoServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->publishes([realpath(__DIR__.'/../../config/api.php') => config_path('api.php')]);

        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        $this->app['Dingo\Api\Http\Middleware\Request']->mergeMiddlewares(
            $this->gatherAppMiddleware($kernel)
        );

        $this->addRequestMiddlewareToBeginning($kernel);

        $this->app['events']->listen(RequestWasMatched::class, function (RequestWasMatched $event) {
            $this->replaceRouteDispatcher();

            $this->updateRouterBindings();
        });
    }

    /**
     * Replace the route dispatcher.
     *
     * @return void
     */
    protected function replaceRouteDispatcher()
    {
        $this->app->singleton('illuminate.route.dispatcher', function ($app) {
            return new ControllerDispatcher($app['api.router.adapter']->getRouter(), $app);
        });
    }

    /**
     * Grab the bindings from the Laravel router and set them on the adapters
     * router.
     *
     * @return void
     */
    protected function updateRouterBindings()
    {
        foreach ($this->getRouterBindings() as $key => $binding) {
            $this->app['api.router.adapter']->getRouter()->bind($key, $binding);
        }
    }

    /**
     * Get the Laravel routers bindings.
     *
     * @return array
     */
    protected function getRouterBindings()
    {
        $property = (new ReflectionClass($this->app['router']))->getProperty('binders');
        $property->setAccessible(true);

        return $property->getValue($this->app['router']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerRouterAdapter();
    }

    /**
     * Register the router adapter.
     *
     * @return void
     */
    protected function registerRouterAdapter()
    {
        $this->app->singleton('api.router.adapter', function ($app) {
            return new LaravelAdapter($app, $this->cloneLaravelRouter(), $app['router']->getRoutes());
        });
    }

    /**
     * Clone the Laravel router and set the middleware on the cloned router.
     *
     * @return \Illuminate\Routing\Router
     */
    protected function cloneLaravelRouter()
    {
        $router = clone $this->app['router'];

        $router->middleware('api.auth', 'Dingo\Api\Http\Middleware\Auth');
        $router->middleware('api.throttle', 'Dingo\Api\Http\Middleware\RateLimit');
        $router->middleware('api.controllers', 'Dingo\Api\Http\Middleware\PrepareController');

        return $router;
    }

    /**
     * Add the request middleware to the beginning of the kernel.
     *
     * @param \Illuminate\Contracts\Http\Kernel $kernel
     *
     * @return void
     */
    protected function addRequestMiddlewareToBeginning(Kernel $kernel)
    {
        $kernel->prependMiddleware('Dingo\Api\Http\Middleware\Request');
    }

    /**
     * Gather the application middleware besides this one so that we can send
     * our request through them, exactly how the developer wanted.
     *
     * @param \Illuminate\Contracts\Http\Kernel $kernel
     *
     * @return array
     */
    protected function gatherAppMiddleware(Kernel $kernel)
    {
        $property = (new ReflectionClass($kernel))->getProperty('middleware');
        $property->setAccessible(true);

        return $property->getValue($kernel);
    }
}
