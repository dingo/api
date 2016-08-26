<?php

namespace Dingo\Api\tests\Routing;

use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\RouteCollection;
use Dingo\Api\Routing\UrlGenerator;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Illuminate\Container\Container;
use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;
use PHPUnit_Framework_TestCase;

class UrlGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->adapter = new RoutingAdapterStub();
        $this->container = new Container();
    }

    public function testVersionRouteGeneration()
    {
        $url = new UrlGenerator($routes = new IlluminateRouteCollection(), $request = Request::create('http://www.foo.com/'));

        $versionedRoutes = new RouteCollection();
        $route = new Route(
            $this->adapter, $this->container, $request,
            new \Illuminate\Routing\Route(['GET'], '/users', ['as' => 'users'])
        );
        $versionedRoutes->add($route);
        $url->setRouteCollections(['v1' => $versionedRoutes]);

        $this->assertSame('/users', $url->version('v1')->route('users', [], false));
    }

    public function testNormalRouteGeneration()
    {
        $url = new UrlGenerator($routes = new IlluminateRouteCollection(), $request = Request::create('http://www.foo.com/'));

        $route = new \Illuminate\Routing\Route(['GET'], '/users', ['as' => 'users']);
        $routes->add($route);

        $url->setRouteCollections(['v1' => new RouteCollection()]);

        $this->assertSame('/users', $url->route('users', [], false));
    }
}
