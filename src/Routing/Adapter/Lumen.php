<?php

namespace Dingo\Api\Routing\Adapter;

use ReflectionClass;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Dingo\Api\Http\Request;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use Laravel\Lumen\Application;
use Dingo\Api\Exception\UnknownVersionException;

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
        if (! isset($this->routes[$version])) {
            throw new UnknownVersionException;
        }

        $this->removeRequestMiddlewareFromApp();

        $routes = $this->routes[$version];

        $this->app->setDispatcher(
            new $this->dispatcher($routes->getData())
        );

        return $this->app->dispatch($request);
    }

    /**
     * Get the URI, methods, and action from the route.
     *
     * @param mixed                   $route
     * @param \Dingo\Api\Http\Request $request
     *
     * @return array
     */
    public function getRouteProperties($route, Request $request)
    {
        $uri = ltrim($request->getRequestUri(), '/');
        $methods = (array) $request->getMethod();
        $action = $route[1];

        if ($request->getMethod() === 'GET') {
            $methods[] = 'HEAD';
        }

        return [$uri, $methods, $action];
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

        foreach ($versions as $version) {
            foreach ($this->breakUriSegments($uri) as $uri) {
                $this->routes[$version]->addRoute($methods, $uri, $action);
            }
        }
    }

    /**
     * Break a URI that has optional segments into individual URIs.
     *
     * @param string $uri
     *
     * @return array
     */
    protected function breakUriSegments($uri)
    {
        if (! str_contains($uri, '?}')) {
            return (array) $uri;
        }

        $segments = preg_split(
            '/\/(\{.*?\})/',
            preg_replace('/\{(.*?)\?\}/', '{$1}', $uri),
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $uris = [];

        while($segments) {
            $uris[] = implode('/', $segments);

            array_pop($segments);
        }

        return $uris;
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

        if (($key = array_search('Dingo\Api\Http\Middleware\Request', $middleware)) !== false) {
            unset($middleware[$key]);
        }

        $property->setValue($this->app, $middleware);
        $property->setAccessible(false);
    }

    /**
     * Get all routes or only for a specific version.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function getRoutes($version = null)
    {
        if (! is_null($version)) {
            return $this->routes[$version];
        }

        return $this->routes;
    }
}
