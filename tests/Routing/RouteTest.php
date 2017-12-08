<?php

namespace Dingo\Api\Tests\Routing;

use Mockery as m;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Illuminate\Routing\Route as IlluminateRoute;

class RouteTest extends TestCase
{
    protected $adapter;
    protected $container;

    public function setUp()
    {
        $this->adapter = new RoutingAdapterStub;
        $this->container = new Container;
    }

    public function tearDown()
    {
        m::close();
    }

    public function testCreatingNewRoute()
    {
        $request = Request::create('foo', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute(['GET', 'HEAD'], 'foo', [
            'scopes' => ['foo', 'bar'],
            'providers' => ['foo'],
            'limit' => 5,
            'expires' => 10,
            'throttle' => \Dingo\Api\Tests\Stubs\BasicThrottleStub::class,
            'version' => ['v1'],
            'conditionalRequest' => false,
            'middleware' => 'foo.bar',
        ]));

        $this->assertSame(['foo', 'bar'], $route->scopes(), 'Route did not setup scopes correctly.');
        $this->assertSame(['foo'], $route->getAuthenticationProviders(), 'Route did not setup authentication providers correctly.');
        $this->assertSame(5, $route->getRateLimit(), 'Route did not setup rate limit correctly.');
        $this->assertSame(10, $route->getRateLimitExpiration(), 'Route did not setup rate limit expiration correctly.');
        $this->assertTrue($route->hasThrottle(), 'Route did not setup throttle correctly.');
        $this->assertInstanceOf(\Dingo\Api\Tests\Stubs\BasicThrottleStub::class, $route->getThrottle(), 'Route did not setup throttle correctly.');
        $this->assertFalse($route->requestIsConditional(), 'Route did not setup conditional request correctly.');
    }

    public function testControllerOptionsMergeAndOverrideRouteOptions()
    {
        $request = Request::create('foo', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute(['GET', 'HEAD'], 'foo', [
            'scopes' => ['foo', 'bar'],
            'providers' => ['foo'],
            'limit' => 5,
            'expires' => 10,
            'throttle' => \Dingo\Api\Tests\Stubs\ThrottleStub::class,
            'version' => ['v1'],
            'conditionalRequest' => false,
            'uses' => \Dingo\Api\Tests\Stubs\RoutingControllerStub::class.'@index',
            'middleware' => 'foo.bar',
        ]));

        $this->assertSame(['foo', 'bar', 'baz', 'bing'], $route->scopes(), 'Route did not setup scopes correctly.');
        $this->assertSame(['foo', 'red', 'black'], $route->getAuthenticationProviders(), 'Route did not setup authentication providers correctly.');
        $this->assertSame(10, $route->getRateLimit(), 'Route did not setup rate limit correctly.');
        $this->assertSame(20, $route->getRateLimitExpiration(), 'Route did not setup rate limit expiration correctly.');
        $this->assertTrue($route->hasThrottle(), 'Route did not setup throttle correctly.');
        $this->assertInstanceOf(\Dingo\Api\Tests\Stubs\BasicThrottleStub::class, $route->getThrottle(), 'Route did not setup throttle correctly.');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute(['GET', 'HEAD'], 'foo/bar', [
            'scopes' => ['foo', 'bar'],
            'providers' => ['foo'],
            'limit' => 5,
            'expires' => 10,
            'throttle' => \Dingo\Api\Tests\Stubs\ThrottleStub::class,
            'version' => ['v1'],
            'conditionalRequest' => false,
            'uses' => \Dingo\Api\Tests\Stubs\RoutingControllerStub::class.'@show',
        ]));

        $this->assertSame(['foo', 'bar', 'baz', 'bing', 'bob'], $route->scopes(), 'Route did not setup scopes correctly.');
        $this->assertSame(['foo'], $route->getAuthenticationProviders(), 'Route did not setup authentication providers correctly.');
        $this->assertSame(10, $route->getRateLimit(), 'Route did not setup rate limit correctly.');
        $this->assertSame(20, $route->getRateLimitExpiration(), 'Route did not setup rate limit expiration correctly.');
        $this->assertTrue($route->hasThrottle(), 'Route did not setup throttle correctly.');
        $this->assertInstanceOf(\Dingo\Api\Tests\Stubs\BasicThrottleStub::class, $route->getThrottle(), 'Route did not setup throttle correctly.');
    }
}
