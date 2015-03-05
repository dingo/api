<?php

namespace Dingo\Api\Tests\Routing;

use Dingo\Api\Routing\Route;
use Mockery as m;
use PHPUnit_Framework_TestCase;

class RouteTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testApiFiltersAreSet()
    {
        // Example : my_uri is a route with a 'foo' before filter
        $route = new Route(
            ['GET'],
            'my_uri',
            [
                'as' => 'my.uri',
                'before' => ['foo'],
                function() {
                    return 'bar';
                }
            ]
        );

        $action = $route->getAction();
        $this->assertEquals([Route::API_FILTER_AUTH, Route::API_FILTER_THROTTLE, 'foo'], $action['before']);
    }

    public function testApiFiltersAreFirstBeforeFilters()
    {
        // Example : my_uri is a route of a group with a 'foo' before filter
        $route = new Route(
            ['GET'],
            'my_uri',
            [
                'as' => 'my.uri',
                'before' => ['foo', Route::API_FILTER_THROTTLE, Route::API_FILTER_AUTH],
                function() {
                    return 'bar';
                }
            ]
        );

        $action = $route->getAction();
        $this->assertEquals([Route::API_FILTER_AUTH, Route::API_FILTER_THROTTLE, 'foo'], $action['before']);
    }
}
