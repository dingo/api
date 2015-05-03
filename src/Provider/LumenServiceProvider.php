<?php

namespace Dingo\Api\Provider;

use ReflectionClass;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\Validator;
use Dingo\Api\Http\Parser\AcceptParser;
use Illuminate\Support\ServiceProvider;
use FastRoute\RouteParser\Std as StdRouteParser;
use Dingo\Api\Routing\Adapter\Lumen\LumenAdapter;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;

class LumenServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->addRequestMiddlewareToBeginning();

        $this->app->singleton('api.router', function ($app) {
            return new Router($app['api.router.adapter'], new AcceptParser('api', 'v1', 'json'), $app);
        });

        $this->app->singleton('api.router.adapter', function ($app) {
            return new LumenAdapter(new StdRouteParser, new GcbDataGenerator, 'FastRoute\Dispatcher\GroupCountBased');
        });

        $this->app->singleton('api.http.validator', function ($app) {
            return new Validator(null, 'api');
        });

        $this->app->alias('api.http.validator', 'Dingo\Api\Http\Validator');
        $this->app->alias('api.router', 'Dingo\Api\Routing\Router');
        $this->app->alias('api.router.adapter', 'Dingo\Api\Routing\Adapter\AdapterInterface');
    }

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
