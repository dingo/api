<?php

namespace Dingo\Api\Http\RateLimit;

use Illuminate\Container\Container;

class UnauthenticatedThrottle extends Throttle
{
    /**
     * Unauthenticated throttle will be matched when request is not authenticated.
     *
     * @param \Illuminate\Container\Container $app
     *
     * @return bool
     */
    public function match(Container $app)
    {
        return ! $app['api.auth']->check();
    }
}
