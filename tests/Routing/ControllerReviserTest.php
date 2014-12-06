<?php

namespace Dingo\Api\Tests\Routing;

use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Routing\ControllerReviser;

class ControllerReviserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->reviser = new ControllerReviser;
    }

    public function testRoutingToRevisedControllerWithWildcardScopes()
    {
        $route = new Route('GET', '/', ['controller' => 'Dingo\Api\Tests\Stubs\WildcardScopeControllerStub@index']);
        $route = $this->reviser->revise($route);
        $this->assertEquals(['foo', 'bar'], $route->getAction()['scopes']);
    }

    public function testRoutingToRevisedControllerWithIndividualScopes()
    {
        $route = new Route('GET', '/', ['controller' => 'Dingo\Api\Tests\Stubs\IndividualScopeControllerStub@index']);
        $route = $this->reviser->revise($route);
        $this->assertEquals(['foo', 'bar'], $route->getAction()['scopes']);
    }

    public function testRoutingToRevisedControllerMergesGroupScopes()
    {
        $route = new Route('GET', '/', ['controller' => 'Dingo\Api\Tests\Stubs\WildcardScopeControllerStub@index', 'scopes' => 'baz']);
        $route = $this->reviser->revise($route);
        $this->assertEquals(['baz', 'foo', 'bar'], $route->getAction()['scopes']);
    }

    public function testRoutingToRevisedControllerWithProtectedMethod()
    {
        $route = new Route('GET', '/', ['controller' => 'Dingo\Api\Tests\Stubs\ProtectedControllerStub@index']);
        $route = $this->reviser->revise($route);
        $this->assertTrue($route->getAction()['protected']);
    }

    public function testRoutingToRevisedControllerWithUnprotectedMethod()
    {
        $route = new Route('GET', '/', ['controller' => 'Dingo\Api\Tests\Stubs\UnprotectedControllerStub@index']);
        $route = $this->reviser->revise($route);
        $this->assertFalse($route->getAction()['protected']);
    }
}
