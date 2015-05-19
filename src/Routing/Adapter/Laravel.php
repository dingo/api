<?php

namespace Dingo\Api\Routing\Adapter;

use Dingo\Api\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteCollection;
use Dingo\Api\Exception\UnknownVersionException;

class Laravel implements Adapter
{
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
     * Create a new laravel routing adapter instance.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
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

        $routes = $this->routes[$version];

        $this->router->setRoutes($routes);

        return $this->router->dispatch($request);
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
     * @return void
     */
    public function addRoute(array $methods, array $versions, $uri, $action)
    {
        $this->createRouteCollections($versions);

        $route = new Route($methods, $uri, $action);

        foreach ($versions as $version) {
            $this->routes[$version]->add($route);
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
}
