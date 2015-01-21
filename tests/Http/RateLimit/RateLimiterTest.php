<?php

namespace Dingo\Api\Tests\Http\RateLimit;

use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Dingo\Api\Http\RateLimit\RateLimiter;

class RateLimiterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->container['config'] = ['cache.driver' => 'array'];
        $this->cache = new CacheManager($this->container);
        $this->limiter = new RateLimiter($this->container, $this->cache, []);

        $this->limiter->setRateLimiter(function ($container, $request) {
            return $request->getClientIp();
        });
    }

    public function testSettingSpecificLimitsOnRouteUsesRouteSpecificThrottle()
    {
        $this->limiter->rateLimitRequest(Request::create('test', 'GET'), 100, 100);

        $throttle = $this->limiter->getThrottle();

        $this->assertInstanceOf('Dingo\Api\Http\RateLimit\RouteSpecificThrottle', $throttle);
        $this->assertSame(['limit' => 100, 'expires' => 100], $throttle->getOptions());
    }

    public function testThrottleWithHighestAmountOfRequestsIsUsedWhenMoreThanOneMatchingThrottle()
    {
        $this->limiter->extend($first = new ThrottleStub(['limit' => 100, 'expires' => 200]));
        $this->limiter->extend($second = new ThrottleStub(['limit' => 99, 'expires' => 400]));

        $this->limiter->rateLimitRequest(Request::create('test', 'GET'));

        $this->assertSame($first, $this->limiter->getThrottle());
    }

    public function testExceedingOfRateLimit()
    {
        $request = Request::create('test', 'GET');

        $this->limiter->rateLimitRequest($request);
        $this->assertFalse($this->limiter->exceededRateLimit());

        $this->limiter->extend(new ThrottleStub(['limit' => 1, 'expires' => 200]));
        $this->limiter->rateLimitRequest($request);
        $this->assertFalse($this->limiter->exceededRateLimit());

        $this->limiter->rateLimitRequest($request);
        $this->assertTrue($this->limiter->exceededRateLimit());
    }

    public function testGettingTheRemainingLimit()
    {
        $this->limiter->extend(new ThrottleStub(['limit' => 10, 'expires' => 200]));
        $this->limiter->rateLimitRequest(Request::create('test', 'GET'));
        $this->assertEquals(9, $this->limiter->getRemainingLimit());
    }
}
