<?php

namespace Dingo\Api\Tests\Http\Middleware;

use Dingo\Api\Exception\RateLimitExceededException;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Middleware\RateLimit;
use Dingo\Api\Http\RateLimit\Handler;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RateLimitTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Router|m\LegacyMockInterface|m\MockInterface
     */
    protected $router;
    /**
     * @var CacheManager
     */
    protected $cache;
    /**
     * @var Handler
     */
    protected $handler;
    /**
     * @var RateLimit
     */
    protected $middleware;

    public function setUp(): void
    {
        $this->container = new Container;
        $this->container['config'] = ['cache.default' => 'array', 'cache.stores.array' => ['driver' => 'array']];

        $this->router = m::mock(Router::class);
        $this->cache = new CacheManager($this->container);
        $this->handler = new Handler($this->container, $this->cache, []);
        $this->middleware = new RateLimit($this->router, $this->handler);

        $this->handler->setRateLimiter(function ($container, $request) {
            return $request->getClientIp();
        });
    }

    public function testMiddlewareBypassesRequestsWithNoRateLimiting()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock(Route::class);
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateLimitExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->handler->extend(new ThrottleStub([], false));

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertSame('foo', $response->getContent());
        $this->assertArrayNotHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayNotHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayNotHasKey('x-ratelimit-reset', $response->headers->all());
    }

    public function testMiddlewareBypassesInternalRequest()
    {
        $request = InternalRequest::create('test', 'GET');

        $route = m::mock(Route::class);
        $route->shouldReceive('hasThrottle')->never();
        $route->shouldReceive('getRateLimit')->never();
        $route->shouldReceive('getRateLimitExpiration')->never();

        $this->router->shouldReceive('getCurrentRoute')->never();

        $this->handler->extend(new ThrottleStub([], false));

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertSame('foo', $response->getContent());
        $this->assertArrayNotHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayNotHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayNotHasKey('x-ratelimit-reset', $response->headers->all());
    }

    public function testRateLimitingPassesAndResponseHeadersAreSet()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock(Route::class);
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateLimitExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->handler->extend(new ThrottleStub(['limit' => 1]));

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('foo');
        });

        $this->assertSame('foo', $response->getContent());
        $this->assertArrayHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-reset', $response->headers->all());
    }

    public function testRateLimitingFailsAndHeadersAreSetOnException()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock(Route::class);
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateLimitExpiration')->once()->andReturn(0);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->handler->extend(new ThrottleStub(['limit' => 0]));

        try {
            $this->middleware->handle($request, function ($request) {
                return new Response('foo');
            });
        } catch (HttpException $exception) {
            $this->assertInstanceOf(RateLimitExceededException::class, $exception);
            $this->assertSame(429, $exception->getStatusCode());
            $this->assertSame('You have exceeded your rate limit.', $exception->getMessage());

            $headers = $exception->getHeaders();
            $this->assertSame($headers['X-RateLimit-Reset'] - time(), $headers['Retry-After']);
            $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
            $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
            $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        }
    }

    public function testRateLimitingWithLimitsSetOnRoute()
    {
        $request = Request::create('test', 'GET');

        $route = m::mock(Route::class);
        $route->shouldReceive('hasThrottle')->once()->andReturn(false);
        $route->shouldReceive('getRateLimit')->once()->andReturn(5);
        $route->shouldReceive('getRateLimitExpiration')->once()->andReturn(10);

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

        $route = m::mock(Route::class);
        $route->shouldReceive('hasThrottle')->once()->andReturn(true);
        $route->shouldReceive('getThrottle')->once()->andReturn(new ThrottleStub(['limit' => 10, 'expires' => 20]));
        $route->shouldReceive('getRateLimit')->once()->andReturn(0);
        $route->shouldReceive('getRateLimitExpiration')->once()->andReturn(0);

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
