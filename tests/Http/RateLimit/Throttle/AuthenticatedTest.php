<?php

namespace Dingo\Api\Tests\Http\RateLimit\Throttle;

use Mockery;
use Dingo\Api\Auth\Auth;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Http\RateLimit\Throttle\Authenticated;

class AuthenticatedTest extends TestCase
{
    public function testThrottleMatchesCorrectly()
    {
        $auth = Mockery::mock(Auth::class)->shouldReceive('check')->once()->andReturn(true)->getMock();
        $container = new Container;
        $container['api.auth'] = $auth;

        $this->assertTrue((new Authenticated)->match($container));
    }
}
