<?php

namespace Dingo\Api\Routing\Adapter;

use ArrayIterator;
use ReflectionClass;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use Laravel\Lumen\Application;
use Dingo\Api\Contract\Routing\Adapter;
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
     * Indicates if the middleware has been removed from the application instance.
     *
     * @var bool
     */
    protected $middlewareRemoved = false;

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
     * @param \Illuminate\Http\Request $request
     * @param string                   $version
     *
     * @return mixed
     */
    public function dispatch(Request $request, $version)
    {
        if (! isset($this->routes[$version])) {
            throw new UnknownVersionException;
        }

        $this->removeMiddlewareFromApp();

        $routes = $this->routes[$version];

        $this->app->setDispatcher(
            new $this->dispatcher($routes->getData())
        );

        $this->normalizeRequestUri($request);

        return $this->app->dispatch($request);
    }

    /**
     * Normalize the request URI so that Lumen can properly dispatch it.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    protected function normalizeRequestUri(Request $request)
    {
        $query = $request->server->get('QUERY_STRING');

        $uri = '/'.trim(str_replace('?'.$query, '', $request->server->get('REQUEST_URI')), '/').($query ? '?'.$query : '');

        $request->server->set('REQUEST_URI', $uri);
    }

    /**
     * Get the URI, methods, and action from the route.
     *
     * @param mixed                    $route
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getRouteProperties($route, Request $request)
    {
        $uri = ltrim(isset($route['uri']) ? $route['uri'] : $request->getRequestUri(), '/');
        $methods = isset($route['methods']) ? $route['methods'] : (array) $request->getMethod();
        $action = (isset($route[1]) && is_array($route[1])) ? $route[1] : $route;

        if ($request->getMethod() === 'GET' && ! in_array('HEAD', $methods)) {
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
        if (! Str::contains($uri, '?}')) {
            return (array) $uri;
        }

        $segments = preg_split(
            '/\/(\{.*?\})/',
            preg_replace('/\{(.*?)\?\}/', '{$1}', $uri),
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $uris = [];

        while ($segments) {
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
                $this->routes[$version] = new RouteCollector($this->parser, clone $this->generator);
            }
        }
    }

    /**
     * Remove the global application middleware as it's run from this packages
     * Request middleware. Lumen runs middleware later in its life cycle
     * which results in some middleware being executed twice.
     *
     * @return void
     */
    protected function removeMiddlewareFromApp()
    {
        if ($this->middlewareRemoved) {
            return;
        }

        $this->middlewareRemoved = true;

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $property->setValue($this->app, []);

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

    /**
     * Get routes in an iterable form.
     *
     * @param string $version
     *
     * @return \ArrayIterator
     */
    public function getIterableRoutes($version = null)
    {
        $iterable = [];

        foreach ($this->getRoutes($version) as $version => $collector) {
            $routeData = $collector->getData();

            // The first element in the array are the static routes that do not have any parameters.
            foreach ($routeData[0] as $uri => $routes) {
                foreach ($routes as $method => $route) {
                    if ($method === 'HEAD') {
                        continue;
                    }
                    $iterable[$version][] = $route;
                }
            }

            // The second element is the more complicated regex routes that have parameters.
            foreach ($routeData[1] as $method => $routes) {
                if ($method === 'HEAD') {
                    continue;
                }

                foreach ($routes as $data) {
                    foreach ($data['routeMap'] as list($route, $parameters)) {
                        $iterable[$version][] = $route;
                    }
                }
            }
        }

        return new ArrayIterator($iterable);
    }

    /**
     * Set the routes on the adapter.
     *
     * @param array $routes
     *
     * @return void
     */
    public function setRoutes(array $routes)
    {
        // Route caching is not implemented for Lumen.
    }

    /**
     * Prepare a route for serialization.
     *
     * @param mixed $route
     *
     * @return mixed
     */
    public function prepareRouteForSerialization($route)
    {
        // Route caching is not implemented for Lumen.
    }

    /**
     * Gather the route middlewares.
     *
     * @param array $route
     *
     * @return array
     */
    public function gatherRouteMiddlewares($route)
    {
        // Route middleware in Lumen is not terminated.
        return [];
    }
}
