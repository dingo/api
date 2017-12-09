<?php

namespace Dingo\Api\Tests\Http\RateLimit;

use Dingo\Api\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Dingo\Api\Http\RateLimit\Handler;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Dingo\Api\Http\RateLimit\Throttle\Route;

class HandlerTest extends TestCase
{
    protected $container;
    protected $cache;
    protected $limiter;

    public function setUp()
    {
        $this->container = new Container;
        $this->container['config'] = ['cache.default' => 'array', 'cache.stores.array' => ['driver' => 'array']];

        $this->cache = new CacheManager($this->container);
        $this->limiter = new Handler($this->container, $this->cache, []);

        $this->limiter->setRateLimiter(function ($container, $request) {
            return $request->getClientIp();
        });
    }

    public function testSettingSpecificLimitsOnRouteUsesRouteSpecificThrottle()
    {
        $this->limiter->rateLimitRequest(Request::create('test', 'GET'), 100, 100);

        $throttle = $this->limiter->getThrottle();

        $this->assertInstanceOf(Route::class, $throttle);
        $this->assertSame(100, $throttle->getLimit());
        $this->assertSame(100, $throttle->getExpires());
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
        $this->assertSame(9, $this->limiter->getRemainingLimit());
    }
}
