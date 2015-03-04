<?php

namespace Dingo\Api\Http\Filter;

use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;

abstract class Filter
{
    /**
     * Indicates if a request is internal.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function requestIsInternal(Request $request)
    {
        return $request instanceof InternalRequest;
    }

    /**
     * Indicates if a route is not protected.
     *
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return bool
     */
    protected function routeNotProtected(Route $route)
    {
        return ! $route->isProtected();
    }

    /**
     * Indicates if a user is logged in.
     *
     * @return bool
     */
    protected function userIsLogged()
    {
        return $this->auth->check();
    }
}
