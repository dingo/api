<?php

namespace Dingo\Api\Routing\Adapter;

use ReflectionClass;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Dingo\Api\Http\Request;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use Laravel\Lumen\Application;

class Lumen implements Adapter
{
    /**
     * Lumen application instance.
     *
     * @var \Laravel\Lumen\Application
     */
    protected $app;

    /**
     * FastRoute parser instance.
     *
     * @var \FastRoute\RouteParser
     */
    protected $parser;

    /**
     * FastRoute data generator instance.
     *
     * @var \FastRoute\DataGenerator
     */
    protected $generator;

    /**
     * FastRoute dispatcher class name.
     *
     * @var string
     */
    protected $dispatcher;

    /**
     * Array of registered routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Create a new lumen adapter instance.
     *
     * @param \Laravel\Lumen\Application $app
     * @param \FastRoute\RouteParser     $parser
     * @param \FastRoute\DataGenerator   $generator
     * @param string                     $dispatcher
     *
     * @return void
     */
    public function __construct(Application $app, RouteParser $parser, DataGenerator $generator, $dispatcher)
    {
        $this->app = $app;
        $this->parser = $parser;
        $this->generator = $generator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch a request.
     *
     * @param \Dingo\Api\Http\Request $request
     * @param string                  $version
     *
     * @return mixed
     */
    public function dispatch(Request $request, $version)
    {
        $this->removeRequestMiddlewareFromApp();

        $routes = $this->routes[$version];

        $this->app->setDispatcher(
            new $this->dispatcher($routes->getData())
        );

        return $this->app->dispatch($request);
    }

    /**
     * Add a route to the appropriate route collection.
     *
     * @param array  $methods
     * @param array  $versions
     * @param string $uri
     * @param mixed  $action
     *
     * @return void
     */
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

    /**
     * Create the route collections for the versions.
     *
     * @param array $versions
     *
     * @return void
     */
    protected function createRouteCollections(array $versions)
    {
        foreach ($versions as $version) {
            if (! isset($this->routes[$version])) {
                $this->routes[$version] = new RouteCollector($this->parser, $this->generator);
            }
        }
    }

    /**
     * Remove the request middleware from the application instance so we don't
     * end up in a continuous loop.
     *
     * @return void
     */
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
