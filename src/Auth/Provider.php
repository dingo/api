<?php

namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

abstract class Provider
{
    /**
     * Authenticate the request and return the authenticated user instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     */
    abstract public function authenticate(Request $request, Route $route);
}
