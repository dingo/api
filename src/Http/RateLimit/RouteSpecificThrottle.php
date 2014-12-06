<?php

namespace Dingo\Api\Http\RateLimit;

use Illuminate\Container\Container;

class RouteSpecificThrottle extends Throttle
{
    /**
     * Route specific throttles always match.
     *
     * @param \Illuminate\Container\Container $app
     *
     * @return bool
     */
    public function match(Container $app)
    {
        return true;
    }
}
