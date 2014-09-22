<?php

namespace Dingo\Api\Tests\Http\RateLimit;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Http\RateLimit\AuthenticatedThrottle;

class AuthenticatedThrottleTest extends PHPUnit_Framework_TestCase
{
    public function testThrottleMatchesCorrectly()
    {
        $auth = Mockery::mock('Dingo\Api\Auth\Authenticator')->shouldReceive('check')->once()->andReturn(true)->getMock();
        $container = new Container;
        $container['api.auth'] = $auth;

        $this->assertTrue((new AuthenticatedThrottle)->match($container));
    }
}
