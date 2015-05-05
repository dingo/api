<?php

namespace Dingo\Api\Provider;

use ReflectionClass;
use Illuminate\Support\ServiceProvider;
use FastRoute\RouteParser\Std as StdRouteParser;
use Dingo\Api\Routing\Adapter\Lumen\LumenAdapter;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;

class LumenServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->addRequestMiddlewareToBeginning();

        $this->app->register('Dingo\Api\Provider\ApiServiceProvider');

        $this->app->singleton('api.router.adapter', function ($app) {
            return new LumenAdapter(new StdRouteParser, new GcbDataGenerator, 'FastRoute\Dispatcher\GroupCountBased');
        });
    }

    /**
     * Add the request middleware to the beginning of the middleware stack on the
     * Lumen application instance.
     *
     * @return void
     */
    protected function addRequestMiddlewareToBeginning()
    {
        $reflection = new ReflectionClass($this->app);

        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($this->app);

        array_unshift($middleware, 'Dingo\Api\Http\Middleware\RequestMiddleware');

        $property->setValue($this->app, $middleware);
        $property->setAccessible(false);
    }
}
