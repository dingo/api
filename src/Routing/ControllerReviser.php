<?php

namespace Dingo\Api\Routing;

use Illuminate\Routing\Route;
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
     * @param \Illuminate\Container\Container  $container
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * Revise a controller route by updating the protection and scopes.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function revise(Route $route)
    {
        if ($this->routingToController($route)) {
            list ($class, $method) = explode('@', $route->getActionName());

            $controller = $this->resolveController($class);

            if ($controller instanceof Controller) {
                $action = $route->getAction();

                $action = $this->reviseProtectedMethods($action, $controller, $method);
                $action = $this->reviseScopedMethods($action, $controller, $method);

                $route->setAction($action);
            }
        }

        return $route;
    }

    /**
     * Determine if the route is routing to a controller.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return bool
     */
    protected function routingToController(Route $route)
    {
        return is_string(array_get($route->getAction(), 'controller'));
    }

    /**
     * Revise the scopes of a controller method. Scopes defined on the
     * controller are merged with those in the route definition.
     *
     * @param  \Illuminate\Routing\Route  $action
     * @param  \Dingo\Api\Routing\Controller  $controller
     * @param  string  $method
     * @return \Illuminate\Routing\Route
     */
    protected function reviseScopedMethods($action, $controller, $method)
    {
        if (! isset($action['scopes'])) {
            $action['scopes'] = [];
        }

        $action['scopes'] = (array) $action['scopes'];

        $scopedMethods = $controller->getScopedMethods();

        if (isset($scopedMethods['*'])) {
            $action['scopes'] = array_merge($action['scopes'], $scopedMethods['*']);
        }

        if (isset($scopedMethods[$method])) {
            $action['scopes'] = array_merge($action['scopes'], $scopedMethods[$method]);
        }

        return $action;
    }

    /**
     * Revise the protected state of a controller method.
     *
     * @param  \Illuminate\Routing\Route  $action
     * @param  \Dingo\Api\Routing\Controller  $controller
     * @param  string  $method
     * @return \Illuminate\Routing\Route
     */
    protected function reviseProtectedMethods($action, $controller, $method)
    {
        if (in_array($method, $controller->getProtectedMethods())) {
            $action['protected'] = true;
        } elseif (in_array($method, $controller->getUnprotectedMethods())) {
            $action['protected'] = false;
        }

        return $action;
    }

    /**
     * Resolve a controller from the container.
     *
     * @param  string  $class
     * @return \Illuminate\Routing\Controller
     */
    protected function resolveController($class)
    {
        $controller = $this->container->make($class);

        if (! $this->container->bound($class)) {
            $this->container->instance($class, $controller);
        }

        return $this->resolvedControllers[$class] = $controller;
    }
}
