<?php

namespace Dingo\Api\Provider;

use ReflectionClass;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Routing\Adapter\LaravelAdapter;

class LaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');

        $this->app->instance('app.middleware', $this->gatherAppMiddleware($kernel));

        $this->addRequestMiddlewareToBeginning($kernel);

        $this->app->register('Dingo\Api\Provider\ApiServiceProvider');

        $this->app->singleton('api.router.adapter', function ($app) {
            return new LaravelAdapter($app['router']);
        });
    }

    protected function addRequestMiddlewareToBeginning($kernel)
    {
        $kernel->prependMiddleware('Dingo\Api\Http\Middleware\RequestMiddleware');
    }

    /**
     * Gather the application middleware besides this one so that we can send
     * our request through them, exactly how the developer wanted.
     *
     * @return array
     */
    protected function gatherAppMiddleware($kernel)
    {
        $reflection = new ReflectionClass($kernel);

        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($kernel);

        return $middleware;
    }
}
