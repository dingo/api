<?php

namespace Dingo\Api\tests\Http\RateLimit;

use Dingo\Api\Http\RateLimit\UnauthenticatedThrottle;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit_Framework_TestCase;

class UnauthenticatedThrottleTest extends PHPUnit_Framework_TestCase
{
    public function testThrottleMatchesCorrectly()
    {
        $auth = Mockery::mock('Dingo\Api\Auth\Authenticator')->shouldReceive('check')->once()->andReturn(true)->getMock();
        $container = new Container();
        $container['api.auth'] = $auth;

        $this->assertFalse((new UnauthenticatedThrottle())->match($container));
    }
}
