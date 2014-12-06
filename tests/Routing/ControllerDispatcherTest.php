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
        'ControllerDispatcherTestResponseValue',
    ];

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

        $container = new Container;
        $container['api.dispatcher'] = Mockery::mock('Dingo\Api\Dispatcher');
        $container['api.auth'] = Mockery::mock('Dingo\Api\Auth\Authenticator');
        $container['api.response'] = Mockery::mock('Dingo\Api\Http\ResponseFactory');

        $dispatcher = new ControllerDispatcher(Mockery::mock('Illuminate\Routing\RouteFiltererInterface'), $container);

        $response = $dispatcher->dispatch($route, $request, 'Dingo\Api\Tests\Stubs\ControllerStub', 'getIndex');

        $this->assertEquals('foo', $response);
        $this->assertInstanceOf('Dingo\Api\Http\ResponseFactory', $_SERVER['ControllerDispatcherTestResponse']);
        $this->assertInstanceOf('Dingo\Api\Auth\Authenticator', $_SERVER['ControllerDispatcherTestAuth']);
        $this->assertInstanceOf('Dingo\Api\Dispatcher', $_SERVER['ControllerDispatcherTestApi']);
    }
}
