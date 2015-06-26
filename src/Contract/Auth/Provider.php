<?php

namespace Dingo\Api\Contract\Auth;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;

interface Provider
{
    /**
     * Authenticate the request and return the authenticated user instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route);
}
