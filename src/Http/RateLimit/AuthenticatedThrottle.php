<?php

namespace Dingo\Api\Http\RateLimit;

use Illuminate\Container\Container;

class AuthenticatedThrottle extends Throttle
{
    /**
     * Authenticated throttle will be matched when request is authenticated.
     *
     * @param \Illuminate\Container\Container $app
     *
     * @return bool
     */
    public function match(Container $app)
    {
        return $app['api.auth']->check();
    }
}
