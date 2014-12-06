<?php

namespace Dingo\Api\Routing;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Routing\UrlGenerator as IlluminateUrlGenerator;

class UrlGenerator extends IlluminateUrlGenerator
{
    /**
     * Create a new URL generator instance.
     *
     * @param \Illuminate\Support\Collection $routes
     * @param \Illuminate\Http\Request       $request
     *
     * @return void
     */
    public function __construct(Collection $routes, Request $request)
    {
        $this->routes = $routes;
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function action($action, $parameters = [], $absolute = true)
    {
        return $this->route($action, $parameters, $absolute, $this->getRouteByAction($action));
    }

    /**
     * {@inheritDoc}
     */
    public function route($name, $parameters = [], $absolute = true, $route = null)
    {
        $route = $route ?: $this->getRouteByName($name);

        $parameters = (array) $parameters;

        if (! is_null($route)) {
            return $this->toRoute($route, $parameters, $absolute);
        }

        throw new InvalidArgumentException('Route ['.$name.'] not defined.');
    }

    /**
     * Get a route by name from either the application routes or API routes.
     *
     * @param string $name
     *
     * @return \Illuminate\Routing\Route|\Dingo\Api\Routing\Route|null
     */
    protected function getRouteByName($name)
    {
        $route = null;

        $this->routes->first(function ($key, $routes) use ($name, &$route) {
            return $route = $routes->getByName($name);
        });

        return $route;
    }

    /**
     * Get a route by name from either the application routes or API routes.
     *
     * @param string $name
     *
     * @return \Illuminate\Routing\Route|\Dingo\Api\Routing\Route|null
     */
    protected function getRouteByAction($action)
    {
        $route = null;

        $this->routes->first(function ($key, $routes) use ($action, &$route) {
            return $route = $routes->getByAction($action);
        });

        return $route;
    }
}
