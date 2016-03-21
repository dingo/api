<?php

namespace Dingo\Api\Provider;

use ReflectionClass;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Http\Kernel;
use Dingo\Api\Event\RequestWasMatched;
use Illuminate\Routing\ControllerDispatcher;
use Dingo\Api\Routing\Adapter\Laravel as LaravelAdapter;

class LaravelServiceProvider extends ApiServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->publishes([
            realpath(__DIR__.'/../../config/api.php') => config_path('api.php'),
        ]);

        $this->replaceRouteDispatcher();
    }

    /**
     * Replace the route dispatcher.
     *
     * @return void
     */
    protected function replaceRouteDispatcher()
    {
        $this->app['events']->listen(RequestWasMatched::class, function (RequestWasMatched $event) {
            $this->app->singleton('illuminate.route.dispatcher', function ($app) {
                return new ControllerDispatcher($app['api.router.adapter']->getRouter(), $app);
            });
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        $this->app->instance('app.middleware', $this->gatherAppMiddleware($kernel));

        $this->addRequestMiddlewareToBeginning($kernel);

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
            $router = new Router($app['events'], $app);

            $router->middleware('api.auth', 'Dingo\Api\Http\Middleware\Auth');
            $router->middleware('api.throttle', 'Dingo\Api\Http\Middleware\RateLimit');

            foreach ($app['router']->getMiddleware() as $name => $class) {
                $router->middleware($name, $class);
            }

            return new LaravelAdapter($router, $app);
        });
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
        $reflection = new ReflectionClass($kernel);

        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($kernel);

        return $middleware;
    }
}
