<?php

namespace Dingo\Api\Routing\Adapter;

use ReflectionClass;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Dingo\Api\Http\Request;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use Laravel\Lumen\Application;
use Dingo\Api\Routing\Adapter\AdapterInterface;

class LumenAdapter implements AdapterInterface
{
    protected $parser;

    protected $generator;

    protected $app;

    protected $dispatcher;

    protected $middleware;

    protected $routes = [];

    public function __construct(Application $app, RouteParser $parser, DataGenerator $generator, $dispatcher)
    {
        $this->app = $app;
        $this->parser = $parser;
        $this->generator = $generator;
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(Request $request, $version)
    {
        $this->removeRequestMiddlewareFromApp();

        $routes = $this->routes[$version];

        $this->app->setDispatcher(
            new $this->dispatcher($routes->getData())
        );

        return $this->app->dispatch($request);
    }

    public function addRoute(array $methods, array $versions, $uri, $action)
    {
        $this->createRouteCollections($versions);

        // Register the route with the Lumen application so that the request is
        // properly dispatched. If we do not add the route then the router
        // will never fire the middlewares.

        foreach ($versions as $version) {
            $this->routes[$version]->addRoute($methods, $uri, $action);
        }
    }

    protected function createRouteCollections(array $versions)
    {
        foreach ($versions as $version) {
            if (! isset($this->routes[$version])) {
                $this->routes[$version] = new RouteCollector($this->parser, $this->generator);
            }
        }
    }

    protected function removeRequestMiddlewareFromApp()
    {
        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($this->app);

        if (($key = array_search('Dingo\Api\Http\Middleware\RequestMiddleware', $middleware)) !== false) {
            unset($middleware[$key]);
        }

        $property->setValue($this->app, $middleware);
        $property->setAccessible(false);
    }
}
