<?php

namespace Dingo\Api\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Matching\ValidatorInterface;

class AcceptValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param \Illuminate\Routing\Route $route
     * @param \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        dd('here');
    }
}
