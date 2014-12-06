<?php

namespace Dingo\Api\Routing;

use BadMethodCallException;
use Illuminate\Container\Container;

class ControllerReviser
{
    /**
     * Illuminate application container.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller reviser instance.
     *
     * @param \Illuminate\Container\Container $container
     *
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * Revise a controller route by updating the protection and scopes.
     *
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return \Dingo\Api\Routing\Route
     */
    public function revise(Route $route)
    {
        if ($this->routingToController($route)) {
            list($class, $method) = explode('@', $route->getActionName());

            $controller = $this->resolveController($class);

            try {
                $this->reviseProtection($route, $controller, $method);
                $this->reviseScopes($route, $controller, $method);
            } catch (BadMethodCallException $exception) {
                // This controller does not utilize the trait.
            }
        }

        return $route;
    }

    /**
     * Determine if the route is routing to a controller.
     *
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return bool
     */
    protected function routingToController(Route $route)
    {
        return is_string(array_get($route->getAction(), 'controller'));
    }

    /**
     * Revise the scopes of a controller method.
     *
     * Scopes defined on the controller are merged with those in the route definition.
     *
     * @param \Dingo\Api\Routing\Route       $action
     * @param \Illuminate\Routing\Controller $controller
     * @param string                         $method
     *
     * @return void
     */
    protected function reviseScopes(Route $route, $controller, $method)
    {
        $properties = $controller->getProperties();

        if (isset($properties['*']['scopes'])) {
            $route->addScopes($properties['*']['scopes']);
        }

        if (isset($properties[$method]['scopes'])) {
            $route->addScopes($properties[$method]['scopes']);
        }
    }

    /**
     * Revise the protected state of a controller method.
     *
     * @param \Dingo\Api\Routing\Route       $action
     * @param \Illuminate\Routing\Controller $controller
     * @param string                         $method
     *
     * @return void
     */
    protected function reviseProtection(Route $route, $controller, $method)
    {
        $properties = $controller->getProperties();

        if (isset($properties['*']['protected'])) {
            $route->setProtected($properties['*']['protected']);
        }

        if (isset($properties[$method]['protected'])) {
            $route->setProtected($properties[$method]['protected']);
        }
    }

    /**
     * Resolve a controller from the container.
     *
     * @param string $class
     *
     * @return \Illuminate\Routing\Controller
     */
    protected function resolveController($class)
    {
        $controller = $this->container->make($class);

        if (! $this->container->bound($class)) {
            $this->container->instance($class, $controller);
        }

        return $controller;
    }
}
