<?php

namespace Dingo\Api\Tests\Stubs;

use Closure;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Container\Container;
use Dingo\Api\Contract\Routing\Adapter;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Http\Response as IlluminateResponse;

class RoutingAdapterStub implements Adapter
{
    protected $routes = [];

    protected $patterns = [];

    public function dispatch(Request $request, $version)
    {
        $routes = $this->routes[$version];

        $route = $this->findRoute($request, $routes);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return (new Pipeline(new Container))
            ->send($request)
            ->through([])
            ->then(function ($request) use ($route) {
                return $this->prepareResponse($request, $route->run($request));
            });
    }

    protected function findRouteClosure(array $action)
    {
        foreach ($action as $value) {
            if ($value instanceof Closure) {
                return $value;
            }
        }
    }

    protected function prepareResponse($request, $response)
    {
        if ($response instanceof IlluminateResponse) {
            $response = Response::makeFromExisting($response);
        } elseif ($response instanceof JsonResponse) {
            $response = Response::makeFromJson($response);
        } else {
            $response = new Response($response);
        }

        return $response->prepare($request);
    }

    protected function findRoute(Request $request, $routeCollection)
    {
        return $routeCollection->match($request);
    }

    public function getRouteProperties($route, Request $request)
    {
        return [$route->uri(), (array) $request->getMethod(), $route->getAction()];
    }

    public function addRoute(array $methods, array $versions, $uri, $action)
    {
        $this->createRouteCollections($versions);

        $route = new IlluminateRoute($methods, $uri, $action);
        $this->addWhereClausesToRoute($route);

        foreach ($versions as $version) {
            $this->routes[$version]->add($route);
        }

        return $route;
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
        return $this->getRoutes($version);
    }

    public function setRoutes(array $routes)
    {
        //
    }

    public function prepareRouteForSerialization($route)
    {
        //
    }

    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }

    protected function createRouteCollections(array $versions)
    {
        foreach ($versions as $version) {
            if (! isset($this->routes[$version])) {
                $this->routes[$version] = new \Illuminate\Routing\RouteCollection;
            }
        }
    }

    protected function addWhereClausesToRoute($route)
    {
        $where = isset($route->getAction()['where']) ? $route->getAction()['where'] : [];

        $route->where(array_merge($this->patterns, $where));

        return $route;
    }
}
