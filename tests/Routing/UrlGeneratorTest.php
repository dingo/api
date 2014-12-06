<?php

namespace Dingo\Api\Tests\Routing;

use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Dingo\Api\Routing\UrlGenerator;
use Dingo\Api\Routing\Route as ApiRoute;
use Illuminate\Routing\Route as IlluminateRoute;
use Dingo\Api\Routing\RouteCollection as ApiRouteCollection;
use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;

class UrlGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->url = new UrlGenerator($this->routes = new Collection, $this->request = Request::create('http://www.foo.com/'));
    }

    public function testGeneratorDefaultsToApplicationRoutes()
    {
        $this->routes->push($app = new IlluminateRouteCollection);
        $app->add(new IlluminateRoute(['GET'], '/app', ['as' => 'foo', 'controller' => 'FooController@foo']));

        $this->routes->push($api = new ApiRouteCollection('v1'));
        $api->add(new ApiRoute(['GET'], '/api', ['as' => 'foo', 'controller' => 'FooController@foo']));

        $this->assertEquals('http://www.foo.com/app', $this->url->route('foo'));
        $this->assertEquals('http://www.foo.com/app', $this->url->action('FooController@foo'));
    }

    public function testGeneratingNamedRoutes()
    {
        $this->routes->push($v1 = new ApiRouteCollection('v1'));
        $this->routes->push($v2 = new ApiRouteCollection('v2'));
        $v1->add(new ApiRoute(['GET'], '/api', ['as' => 'foo']));
        $v2->add(new ApiRoute(['GET'], '/api/foo', ['as' => 'foo']));
        $v2->add(new ApiRoute(['GET'], '/api/bar', ['as' => 'bar']));

        $this->assertEquals('http://www.foo.com/api', $this->url->route('foo'));
        $this->assertEquals('http://www.foo.com/api/bar', $this->url->route('bar'));
    }

    public function testGeneratingActionRoutes()
    {
        $this->routes->push($v1 = new ApiRouteCollection('v1'));
        $this->routes->push($v2 = new ApiRouteCollection('v2'));
        $v1->add(new ApiRoute(['GET'], '/api', ['controller' => 'FooController@foo']));
        $v2->add(new ApiRoute(['GET'], '/api/foo', ['controller' => 'FooController@foo']));
        $v2->add(new ApiRoute(['GET'], '/api/bar', ['controller' => 'FooController@bar']));

        $this->assertEquals('http://www.foo.com/api', $this->url->action('FooController@foo'));
        $this->assertEquals('http://www.foo.com/api/bar', $this->url->action('FooController@bar'));
    }
}
