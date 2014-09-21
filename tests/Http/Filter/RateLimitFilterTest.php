<?php

namespace Dingo\Api\Tests\Http\Filter;

use Mockery;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Cache\CacheManager;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Dingo\Api\Http\RateLimit\RateLimiter;
use Dingo\Api\Http\Filter\RateLimitFilter;

class RateLimitFilterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->container['config'] = ['cache.driver' => 'array'];

        $this->router = new Router(new Dispatcher($this->container), $this->container);
        $this->cache = new CacheManager($this->container);
        $this->limiter = new RateLimiter($this->cache, $this->container, []);
        $this->filter = new RateLimitFilter($this->router, $this->limiter);
    }


    public function testFilterBypassesInternalRequests()
    {
        $request = InternalRequest::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true]);

        $this->assertNull($this->filter->filter($route, $request));
    }


    public function testFilterBypassesRequestsWithNoRateLimiting()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true]);

        $this->limiter->extend(new ThrottleStub([], false));

        $this->assertNull($this->filter->filter($route, $request));
    }


    public function testRateLimitingPassesAndResponseHeadersAreSet()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true, 'uses' => function () {
            return 'test';
        }]);

        $this->router->getRoutes()->add($route);
        $this->limiter->extend(new ThrottleStub(['limit' => 1]));

        $this->assertNull($this->filter->filter($route, $request));

        $response = $this->router->dispatch($request);
        $this->assertArrayHasKey('x-ratelimit-limit', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-remaining', $response->headers->all());
        $this->assertArrayHasKey('x-ratelimit-reset', $response->headers->all());
    }


    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testRateLimitingFailsAndExceptionIsThrown()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true]);

        $this->limiter->extend(new ThrottleStub([]));

        $this->filter->filter($route, $request);
    }
}
