<?php

namespace Dingo\Api\Tests\Routing;

use Mockery;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Routing\ControllerDispatcher;

class ControllerDispatcherTest extends PHPUnit_Framework_TestCase
{
    protected $keys = [
        'ControllerDispatcherTestApiValue',
        'ControllerDispatcherTestAuthValue',
        'ControllerDispatcherTestResponseValue'
    ];


    public function setUp()
    {
        foreach ($this->keys as $key) {
            $_SERVER[$key] = null;
        }
    }


    public function tearDown()
    {
        foreach ($this->keys as $key) {
            unset($_SERVER[$key]);
        }
    }


    public function testControllerDependenciesAreInjectedWhenControllerIsResolved()
    {
        $request = Request::create('test', 'GET');
        $route = new Route(['GET'], 'test', ['uses' => function () {}]);
        $route->bind($request);
        $dispatcher = new ControllerDispatcher(Mockery::mock('Illuminate\Routing\RouteFiltererInterface'), new Container);

        $dispatcher->setAuthenticator(Mockery::mock('Dingo\Api\Auth\Authenticator'));
        $dispatcher->setDispatcher(Mockery::mock('Dingo\Api\Dispatcher'));
        $dispatcher->setResponseFactory(Mockery::mock('Dingo\Api\Http\Response\Factory'));

        $response = $dispatcher->dispatch($route, $request, 'Dingo\Api\Tests\Stubs\ControllerStub', 'getIndex');

        $this->assertEquals('foo', $response);
        $this->assertInstanceOf('Dingo\Api\Http\Response\Factory', $_SERVER['ControllerDispatcherTestResponse']);
        $this->assertInstanceOf('Dingo\Api\Auth\Authenticator', $_SERVER['ControllerDispatcherTestAuth']);
        $this->assertInstanceOf('Dingo\Api\Dispatcher', $_SERVER['ControllerDispatcherTestApi']);
    }
}
