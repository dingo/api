<?php

namespace Dingo\Api\Tests\Stubs;

use Closure;
use ArrayIterator;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Contract\Routing\Adapter;
use Illuminate\Http\Response as IlluminateResponse;

class RoutingAdapterStub implements Adapter
{
    protected $routes = [];

    public function dispatch(Request $request, $version)
    {
        $routes = $this->routes[$version];

        $route = $this->findRoute($request, $routes);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        if (isset($route['action']['uses'])) {
            list($controller, $method) = explode('@', $route['action']['uses']);

            $controller = new $controller;

            $response = $controller->$method();
        } else {
            $response = call_user_func($this->findRouteClosure($route['action']));
        }

        return $this->prepare($response);
    }

    protected function findRouteClosure(array $action)
    {
        foreach ($action as $value) {
            if ($value instanceof Closure) {
                return $value;
            }
        }
    }

    protected function prepare($response)
    {
        if (! $response instanceof Response && ! $response instanceof IlluminateResponse) {
            $response = new Response($response);
        }

        return $response;
    }

    protected function findRoute(Request $request, array $routes)
    {
        foreach ($routes as $key => $route) {
            list($method, $domain, $uri) = explode(' ', $key);

            if ($request->getMethod() == $method && $request->getHost() == $domain && trim($request->getPathInfo(), '/') === trim($route['uri'], '/')) {
                return $route;
            }
        }
    }

    public function getRouteProperties($route, Request $request)
    {
        return [$route['uri'], (array) $request->getMethod(), $route['action']];
    }

    public function addRoute(array $methods, array $versions, $uri, $action)
    {
        foreach ($versions as $version) {
            if (! isset($this->routes[$version])) {
                $this->routes[$version] = [];
            }

            if (! isset($action['domain'])) {
                $action['domain'] = 'localhost';
            }

            if (str_contains($uri, '?}')) {
                $uri = preg_replace('/\/\{(.*?)\?\}/', '', $uri);
            }

            foreach ($methods as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                if (! isset($this->routes[$version])) {
                    $this->routes[$version] = [];
                }

                $this->routes[$version][$method.' '.$action['domain'].' '.$uri] = compact('uri', 'action');
            }
        }
    }

    public function getRoutes($version = null)
    {
        if (! is_null($version)) {
            return $this->routes[$version];
        }

        return $this->routes;
    }

    public function getIterableRoutes($version = null)
    {
        return new ArrayIterator($this->getRoutes($version));
    }

    public function setRoutes(array $routes)
    {
        //
    }

    public function prepareRouteForSerialization($route)
    {
        //
    }
}
