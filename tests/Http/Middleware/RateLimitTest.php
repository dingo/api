<?php

namespace Dingo\Api\Tests\Http\Middleware;

use Mockery as m;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use PHPUnit_Framework_TestCase;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Dingo\Api\Http\RateLimit\Handler;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Dingo\Api\Http\Middleware\RateLimit;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RateLimitTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->container['config'] = ['cache.default' => 'array', 'cache.stores.array' => ['driver' => 'array']];

        $this->router = m::mock('Dingo\Api\Routing\Router');
        $this->cache = new CacheManager($this->container);
        $this->handler = new Handler($this->container, $this->cache, []);
        $this->middleware = new RateLimit($this->router, $this->handler);

        $this->handler->setRateLimiter(function ($container, $request) {
            return $request->getClientIp();
        });
    }

    public function tearDown()
    {
        m::close();
    }

    public function testMiddlewareBypassesRequestsWithNoRateLimiting()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->handler->extend(new ThrottleStub([], false));

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertEquals('foo', $response->getContent());
        $this->assertArrayNotHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayNotHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayNotHasKey('x-ratelimit-reset', $response->headers->all());
    }

    public function testRateLimitingPassesAndResponseHeadersAreSet()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->handler->extend(new ThrottleStub(['limit' => 1]));

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertEquals('foo', $response->getContent());
        $this->assertArrayHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-reset', $response->headers->all());
    }

    public function testRateLimitingFailsAndHeadersAreSetOnException()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->handler->extend(new ThrottleStub(['limit' => 0]));

        try {
            $this->middleware->handle($request, function ($request) {
                return new Response('foo');
            });
        } catch (HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode());
            $this->assertEquals('You have exceeded your rate limit.', $exception->getMessage());
            $this->assertArrayHasKey('X-RateLimit-Limit', $exception->getHeaders());
            $this->assertArrayHasKey('X-RateLimit-Remaining', $exception->getHeaders());
            $this->assertArrayHasKey('X-RateLimit-Reset', $exception->getHeaders());
        }
    }

    public function testRateLimitingWithLimitsSetOnRoute()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(5);
        $route->shouldReceive('getRateExpiration')->once()->andReturn(10);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertArrayHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-reset', $response->headers->all());
        $this->assertEquals(4, $response->headers->get('x-ratelimit-remaining'));
        $this->assertEquals(5, $response->headers->get('x-ratelimit-limit'));
    }

    public function testRateLimitingWithRouteThrottle()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('hasThrottle')->once()->andReturn(true);
        $route->shouldReceive('getThrottle')->once()->andReturn(new ThrottleStub(['limit' => 10, 'expires' => 20]));
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertArrayHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-reset', $response->headers->all());
        $this->assertEquals(9, $response->headers->get('x-ratelimit-remaining'));
        $this->assertEquals(10, $response->headers->get('x-ratelimit-limit'));
    }
}
