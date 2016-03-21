<?php

namespace Dingo\Api\Routing\Adapter;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteCollection;
use Dingo\Api\Contract\Routing\Adapter;
use Dingo\Api\Exception\UnknownVersionException;
use Illuminate\Contracts\Foundation\Application;

class Laravel implements Adapter
{
    /**
     * Laravel application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Laravel router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Old routes already defined on the router.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $oldRoutes;

    /**
     * Array of registered routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Create a new laravel routing adapter instance.
     *
     * @param \Illuminate\Routing\Router                   $router
     * @param \Illuminate\Contracts\Foundation\Application $application
     *
     * @return void
     */
    public function __construct(Router $router, Application $app)
    {
        $this->router = $router;
        $this->app = $app;
        $this->oldRoutes = $app['routes'];
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

        $this->setUrlGeneratorRoutes($this->routes[$version]);

        return $this->router->dispatch($request);
    }

    /**
     * Update url generator routes.
     *
     * @param \Illuminate\Routing\RouteCollection $routes
     *
     * @return void
     */
    public function setUrlGeneratorRoutes(RouteCollection $routes)
    {
        // Add new routes to existing laravel routes.
        foreach ($routes as $route) {
            $this->oldRoutes->add($route);
        }

        $this->app['url']->setRoutes($this->oldRoutes);
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
