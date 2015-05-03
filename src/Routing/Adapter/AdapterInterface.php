<?php

namespace Dingo\Api\Routing\Adapter;

use Closure;
use Dingo\Api\Http\Request;

interface AdapterInterface
{
    public function dispatch(Request $request, $version);

    public function addRoute(array $methods, array $versions, $uri, $action);
}
