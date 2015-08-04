<?php

namespace Dingo\Api\Tests\Http\Middleware;

use Mockery as m;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Http\Middleware\Auth;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->adapter = new RoutingAdapterStub;
        $this->router = m::mock('Dingo\Api\Routing\Router');
        $this->auth = m::mock('Dingo\Api\Auth\Auth');
        $this->middleware = new Auth($this->router, $this->auth);
    }

    public function testProtectedRouteFiresAuthenticationAndPasses()
    {
        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, [
            'uri' => '/test',
            'action' => [
                'providers' => [],
            ],
        ]);

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andReturn(null);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function ($handledRequest) use ($request) {
            $this->assertEquals($handledRequest, $request);
        });
    }

    public function testProtectedRouteAlreadyLoggedIn()
    {
        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, [
            'uri' => '/test',
            'action' => [
                'providers' => [],
            ],
        ]);

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(true);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function ($handledRequest) use ($request) {
            $this->assertEquals($handledRequest, $request);
        });
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testAuthenticationFailsAndExceptionIsThrown()
    {
        $exception = new UnauthorizedHttpException('test');

        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, [
            'uri' => '/test',
            'action' => [
                'providers' => [],
            ],
        ]);

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andThrow($exception);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function () {
            //
        });
    }
}
