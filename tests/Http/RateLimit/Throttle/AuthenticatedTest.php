<?php

namespace Dingo\Api\Tests\Http\RateLimit\Throttle;

use Dingo\Api\Auth\Auth;
use Dingo\Api\Http\RateLimit\Throttle\Authenticated;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Container\Container;
use Mockery;

class AuthenticatedTest extends BaseTestCase
{
    public function testThrottleMatchesCorrectly()
    {
        $auth = Mockery::mock(Auth::class)->shouldReceive('check')->once()->andReturn(true)->getMock();
        $container = new Container;
        $container['api.auth'] = $auth;

        $this->assertTrue((new Authenticated)->match($container));
    }
}
