<?php

namespace Dingo\Api\Routing\Adapter;

use Closure;
use Dingo\Api\Http\Request;

interface AdapterInterface
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
}
