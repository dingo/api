<?php

namespace Dingo\Api\Routing\Adapter\Lumen;

use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Dingo\Api\Http\Request;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use Dingo\Api\Routing\Adapter\AdapterInterface;

class LumenAdapter implements AdapterInterface
{
    protected $parser;

    protected $generator;

    protected $app;

    protected $dispatcher;

    protected $routes = [];

    public function __construct(RouteParser $parser, DataGenerator $generator, $dispatcher, Application $app = null)
    {
        $this->parser = $parser;
        $this->generator = $generator;
        $this->dispatcher = $dispatcher;
        $this->app = $app ?: new Application;
    }

    public function dispatch(Request $request, $version)
    {
        $routes = $this->routes[$version];

        $dispatcher = new $this->dispatcher($routes->getData());

        $this->app->setDispatcher($dispatcher);

        return $this->app->dispatch($request);
    }

    public function addRoute(array $methods, array $versions, $uri, $action)
    {
        $this->createRouteCollections($versions);

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
}
