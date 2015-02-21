<?php

namespace Dingo\Api\Tests\Http\Filter;

use Mockery;
use Dingo\Api\Properties;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dingo\Api\Http\Filter\AuthFilter;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthFilterTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->events = new Dispatcher($this->container);
        $this->router = new Router($this->events, new Properties, $this->container);
        $this->auth = Mockery::mock('Dingo\Api\Auth\Authenticator');
        $this->filter = new AuthFilter($this->router, $this->events, $this->auth);
    }

    public function testFilterBypassesUnprotectedRoutes()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => false]);

        $this->assertNull($this->filter->filter($route, $request));
    }

    public function testFilterBypassesAlreadyLoggedInUsers()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true]);

        $this->auth->shouldReceive('check')->once()->andReturn(true);

        $this->assertNull($this->filter->filter($route, $request));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testAuthFailsAndExceptionIsThrown()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true]);
        $exception = new UnauthorizedHttpException('test');

        $this->auth->shouldReceive('check')->once()->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andThrow($exception);

        $this->filter->filter($route, $request);
    }

    public function testAuthSucceedsWithSpecificProvidersAndNoResponseIsReturned()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['protected' => true, 'providers' => 'foo']);

        $this->auth->shouldReceive('check')->once()->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with(['test', 'foo']);

        $this->assertNull($this->filter->filter($route, $request, 'test'));
    }
}
