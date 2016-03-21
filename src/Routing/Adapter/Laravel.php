<?php

namespace Dingo\Api\Routing\Adapter;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteCollection;
use Dingo\Api\Contract\Routing\Adapter;
use Illuminate\Contracts\Container\Container;
use Dingo\Api\Exception\UnknownVersionException;

class Laravel implements Adapter
{
    /**
     * Application container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Laravel router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Array of registered routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Application routes.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $applicationRoutes = [];

    /**
     * Create a new laravel routing adapter instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Routing\Router                $router
     * @param \Illuminate\Routing\RouteCollection       $applicationRoutes
     *
     * @return void
     */
    public function __construct(Container $container, Router $router, RouteCollection $applicationRoutes)
    {
        $this->container = $container;
        $this->router = $router;
        $this->applicationRoutes = $applicationRoutes;
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

        $this->router->setRoutes($this->routes[$version]);

        // Because the above call will reset the routes defined on the applications
        // UrlGenerator we will simply rebind the routes to the application
        // container which will trigger the rebinding event.
        $this->container->instance('routes', $this->applicationRoutes);

        return $this->router->dispatch($request);
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
        return [$route->getUri(), $route->getMethods(), $route->getAction()];
    }

    /**
     * Add a route to the appropriate route collection.
     *
     * @param array  $methods
     * @param array  $versions
     * @param string $uri
     * @param mixed  $action
     *
     * @return \Illuminate\Routing\Route
     */
    public function addRoute(array $methods, array $versions, $uri, $action)
    {
        $this->createRouteCollections($versions);

        $route = new Route($methods, $uri, $action);
        $route->where($action['where']);

        foreach ($versions as $version) {
            $this->routes[$version]->add($route);
        }

        return $route;
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
                $this->routes[$version] = new RouteCollection;
            }
        }
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
     * Get a normalized iterable set of routes.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function getIterableRoutes($version = null)
    {
        return $this->getRoutes($version);
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
        $this->routes = $routes;
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
        $route->prepareForSerialization();

        return $route;
    }

    /**
     * Gather the route middlewares.
     *
     * @param \Illuminate\Routing\Route $route
     *
     * @return array
     */
    public function gatherRouteMiddlewares($route)
    {
        return $this->router->gatherRouteMiddlewares($route);
    }

    /**
     * Get the Laravel router instance.
     *
     * @return \Illuminate\Routing\Router
     */
    public function getRouter()
    {
        return $this->router;
    }
}
