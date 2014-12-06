<?php

namespace Dingo\Api\Http\RateLimit;

use Illuminate\Container\Container;

interface ThrottleInterface
{
    /**
     * Attempt to match the throttle against a given condition.
     *
     * @param \Illuminate\Container\Container $app
     *
     * @return bool
     */
    public function match(Container $app);

    /**
     * Get the throttles request limit.
     *
     * @return int
     */
    public function getLimit();

    /**
     * Get the time in minutes that the throttles request limit will expire.
     *
     * @return int
     */
    public function getExpires();
}
