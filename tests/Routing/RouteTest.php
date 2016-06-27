<?php

namespace Dingo\Api\Tests\Routing;

use Mockery as m;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Dingo\Api\Tests\Stubs\BasicThrottleStub;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Dingo\Api\Tests\Stubs\RoutingControllerStub;

class RouteTest extends PHPUnit_Framework_TestCase
{
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

        $route = new Route($this->adapter, $this->container, $request, [
            'uri' => 'foo',
            'methods' => ['GET', 'HEAD'],
            'action' => [
                'scopes' => ['foo', 'bar'],
                'providers' => ['foo'],
                'limit' => 5,
                'expires' => 10,
                'throttle' => BasicThrottleStub::class,
                'version' => ['v1'],
                'conditionalRequest' => false,
            ],
        ]);

        $this->assertEquals(['foo', 'bar'], $route->scopes(), 'Route did not setup scopes correctly.');
        $this->assertEquals(['foo'], $route->getAuthenticationProviders(), 'Route did not setup authentication providers correctly.');
        $this->assertEquals(5, $route->getRateLimit(), 'Route did not setup rate limit correctly.');
        $this->assertEquals(10, $route->getRateLimitExpiration(), 'Route did not setup rate limit expiration correctly.');
        $this->assertTrue($route->hasThrottle(), 'Route did not setup throttle correctly.');
        $this->assertInstanceOf(BasicThrottleStub::class, $route->getThrottle(), 'Route did not setup throttle correctly.');
        $this->assertFalse($route->requestIsConditional(), 'Route did not setup conditional request correctly.');
    }

    public function testControllerOptionsMergeAndOverrideRouteOptions()
    {
        $request = Request::create('foo', 'GET');

        $route = new Route($this->adapter, $this->container, $request, [
            'uri' => 'foo',
            'methods' => ['GET', 'HEAD'],
            'action' => [
                'scopes' => ['foo', 'bar'],
                'providers' => ['foo'],
                'limit' => 5,
                'expires' => 10,
                'throttle' => ThrottleStub::class,
                'version' => ['v1'],
                'conditionalRequest' => false,
                'uses' => RoutingControllerStub::class.'@index',
            ],
        ]);

        $this->assertEquals(['foo', 'bar', 'baz', 'bing'], $route->scopes(), 'Route did not setup scopes correctly.');
        $this->assertEquals(['foo', 'red', 'black'], $route->getAuthenticationProviders(), 'Route did not setup authentication providers correctly.');
        $this->assertEquals(10, $route->getRateLimit(), 'Route did not setup rate limit correctly.');
        $this->assertEquals(20, $route->getRateLimitExpiration(), 'Route did not setup rate limit expiration correctly.');
        $this->assertTrue($route->hasThrottle(), 'Route did not setup throttle correctly.');
        $this->assertInstanceOf(BasicThrottleStub::class, $route->getThrottle(), 'Route did not setup throttle correctly.');

        $route = new Route($this->adapter, $this->container, $request, [
            'uri' => 'foo/bar',
            'methods' => ['GET', 'HEAD'],
            'action' => [
                'scopes' => ['foo', 'bar'],
                'providers' => ['foo'],
                'limit' => 5,
                'expires' => 10,
                'throttle' => ThrottleStub::class,
                'version' => ['v1'],
                'conditionalRequest' => false,
                'uses' => RoutingControllerStub::class.'@show',
            ],
        ]);

        $this->assertEquals(['foo', 'bar', 'baz', 'bing', 'bob'], $route->scopes(), 'Route did not setup scopes correctly.');
        $this->assertEquals(['foo'], $route->getAuthenticationProviders(), 'Route did not setup authentication providers correctly.');
        $this->assertEquals(10, $route->getRateLimit(), 'Route did not setup rate limit correctly.');
        $this->assertEquals(20, $route->getRateLimitExpiration(), 'Route did not setup rate limit expiration correctly.');
        $this->assertTrue($route->hasThrottle(), 'Route did not setup throttle correctly.');
        $this->assertInstanceOf(BasicThrottleStub::class, $route->getThrottle(), 'Route did not setup throttle correctly.');
    }
}
