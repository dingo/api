<?php

namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;

interface ProviderInterface
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
