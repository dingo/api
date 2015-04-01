<?php

namespace Dingo\Api\Tests\Routing;

use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;

class RouteTest extends PHPUnit_Framework_TestCase
{
    public function testApiFiltersAreSet()
    {
        $route = new Route(['GET'], 'test', ['before' => ['foo'], function() {
            return 'bar';
        }]);

        $action = $route->getAction();

        $this->assertEquals([Route::API_FILTER_AUTH, Route::API_FILTER_THROTTLE, 'foo'], $action['before']);
    }

    public function testApiFiltersAreFirstBeforeFilters()
    {
        $route = new Route(['GET'], 'test', ['before' => ['foo', Route::API_FILTER_THROTTLE, Route::API_FILTER_AUTH], function() {
            return 'bar';
        }]);

        $action = $route->getAction();

        $this->assertEquals([Route::API_FILTER_AUTH, Route::API_FILTER_THROTTLE, 'foo'], $action['before']);
    }
}
