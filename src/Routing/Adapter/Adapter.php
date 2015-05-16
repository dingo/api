<?php

namespace Dingo\Api\Routing\Adapter;

use Closure;
use Dingo\Api\Http\Request;

interface Adapter
{
    /**
     * Dispatch a request.
     *
     * @param \Dingo\Api\Http\Request $request
     * @param string                  $version
     *
     * @return mixed
     */
    public function dispatch(Request $request, $version);

    /**
     * Get the URI, methods, and action from the route.
     *
     * @param mixed                   $route
     * @param \Dingo\Api\Http\Request $request
     *
     * @return array
     */
    public function getRouteProperties($route, Request $request);

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
    public function addRoute(array $methods, array $versions, $uri, $action);

    /**
     * Get all routes or only for a specific version.
     *
     * @param string $version
     *
     * @return mixed
     */
    public function getRoutes($version = null);
}
