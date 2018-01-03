<?php

namespace Dingo\Api\Tests\Http\Middleware;

use Mockery as m;
use Dingo\Api\Auth\Auth;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Illuminate\Routing\Route as IlluminateRoute;
use Dingo\Api\Http\Middleware\Auth as AuthMiddleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthTest extends TestCase
{
    protected $container;
    protected $adapter;
    protected $router;
    protected $auth;
    protected $middleware;

    public function setUp()
    {
        $this->container = new Container;
        $this->adapter = new RoutingAdapterStub;
        $this->router = m::mock(Router::class);
        $this->auth = m::mock(Auth::class);
        $this->middleware = new AuthMiddleware($this->router, $this->auth);
    }

    public function testProtectedRouteFiresAuthenticationAndPasses()
    {
        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute('GET', '/test', [
            'providers' => [],
        ]));

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andReturn(null);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function ($handledRequest) use ($request) {
            $this->assertSame($handledRequest, $request);
        });
    }

    public function testProtectedRouteAlreadyLoggedIn()
    {
        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute('GET', '/test', [
            'providers' => [],
        ]));

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(true);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function ($handledRequest) use ($request) {
            $this->assertSame($handledRequest, $request);
        });
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testAuthenticationFailsAndExceptionIsThrown()
    {
        $exception = new UnauthorizedHttpException('test');

        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute('GET', '/test', [
            'providers' => [],
        ]));

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andThrow($exception);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function () {
            //
        });
    }
}
