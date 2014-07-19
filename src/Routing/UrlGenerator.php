<?php namespace Dingo\Api\Routing;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator as IlluminateUrlGenerator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class UrlGenerator extends IlluminateUrlGenerator
{
    /**
     * Get the URL to a controller action.
     *
     * @param  string $action
     * @param  mixed  $parameters
     * @param  bool   $absolute
     *
     * @return string
     */
    public function action($action, $parameters = array(), $absolute = true)
    {
        try {
            return $this->route($action, $parameters, $absolute, $this->routes->getByAction($action));
        } catch (InvalidArgumentException $e) {
            $apiRoutes = app('router')->getApiRoutes();

            foreach ($apiRoutes as $apiRoute) {
                $route = $apiRoute->getByAction($action);
                if (!is_null($route)) {
                    return $this->route($action, $parameters, $absolute, $route);
                }
            }
        }
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string                    $name
     * @param  mixed                     $parameters
     * @param  bool                      $absolute
     * @param  \Illuminate\Routing\Route $route
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = array(), $absolute = true, $route = null)
    {
        $route = $route ?: $this->routes->getByName($name);

        $parameters = (array) $parameters;

        if (!is_null($route)) {
            return $this->toRoute($route, $parameters, $absolute);
        } else {
            $apiRoutes = app('router')->getApiRoutes();

            foreach ($apiRoutes as $apiRoute) {
                $route = $apiRoute->getByName($name);
                if (!is_null($route)) {
                    return $this->toRoute($route, $parameters, $absolute);
                }
            }
            throw new InvalidArgumentException("Route [{$name}] not defined.");
        }
    }
}