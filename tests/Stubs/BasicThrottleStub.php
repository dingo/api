<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Container\Container;
use Dingo\Api\Contract\Http\RateLimit\Throttle;

class BasicThrottleStub implements Throttle
{
    public function match(Container $app)
    {
        return true;
    }

    public function getLimit()
    {
        return 15;
    }

    public function getExpires()
    {
        return 10;
    }
}
