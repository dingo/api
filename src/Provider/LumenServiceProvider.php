<?php

namespace Dingo\Api\Provider;

use ReflectionClass;
use Dingo\Api\Http\Middleware\Auth;
use Dingo\Api\Http\Middleware\Request;
use Dingo\Api\Http\Middleware\RateLimit;
use FastRoute\Dispatcher\GroupCountBased;
use Dingo\Api\Http\Middleware\PrepareController;
use FastRoute\RouteParser\Std as StdRouteParser;
use Illuminate\Http\Request as IlluminateRequest;
use Dingo\Api\Routing\Adapter\Lumen as LumenAdapter;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;

class LumenServiceProvider extends DingoServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->app->configure('api');

        $reflection = new ReflectionClass($this->app);

        $this->app[Request::class]->mergeMiddlewares(
            $this->gatherAppMiddleware($reflection)
        );

        $this->addRequestMiddlewareToBeginning($reflection);

        // Because Lumen sets the route resolver at a very weird point we're going to
        // have to use reflection whenever the request instance is rebound to
        // set the route resolver to get the current route.
        $this->app->rebinding(IlluminateRequest::class, function ($app, $request) {
            $request->setRouteResolver(function () use ($app) {
                $reflection = new ReflectionClass($app);

                $property = $reflection->getProperty('currentRoute');
                $property->setAccessible(true);

                return $property->getValue($app);
            });
        });

        $this->app->routeMiddleware([
            'api.auth' => Auth::class,
            'api.throttle' => RateLimit::class,
            'api.controllers' => PrepareController::class,
        ]);
    }

    /**
     * Setup the configuration.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $this->app->configure('api');

        parent::setupConfig();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->singleton('api.router.adapter', function ($app) {
            return new LumenAdapter($app, new StdRouteParser, new GcbDataGenerator, GroupCountBased::class);
        });
    }

    /**
     * Add the request middleware to the beginning of the middleware stack on the
     * Lumen application instance.
     *
     * @param \ReflectionClass $reflection
     *
     * @return void
     */
    protected function addRequestMiddlewareToBeginning(ReflectionClass $reflection)
    {
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($this->app);

        array_unshift($middleware, Request::class);

        $property->setValue($this->app, $middleware);
        $property->setAccessible(false);
    }

    /**
     * Gather the application middleware besides this one so that we can send
     * our request through them, exactly how the developer wanted.
     *
     * @param \ReflectionClass $reflection
     *
     * @return array
     */
    protected function gatherAppMiddleware(ReflectionClass $reflection)
    {
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($this->app);

        return $middleware;
    }
}
