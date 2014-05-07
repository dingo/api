<?php

use Illuminate\Routing\Route;
use Dingo\Api\Routing\ControllerReviser;

class RoutingControllerReviserTest extends PHPUnit_Framework_TestCase {


	public function testRoutingToRevisedControllerWithWildcardScopes()
	{
		$reviser = new ControllerReviser;
		$route = $reviser->revise(new Route('GET', '/', ['controller' => 'WildcardScopeControllerStub@index']));
		$this->assertEquals(['foo', 'bar'], $route->getAction()['scopes']);
	}


	public function testRoutingToRevisedControllerWithIndividualScopes()
	{
		$reviser = new ControllerReviser;
		$route = $reviser->revise(new Route('GET', '/', ['controller' => 'IndividualScopeControllerStub@index']));
		$this->assertEquals(['foo', 'bar'], $route->getAction()['scopes']);
	}


	public function testRoutingToRevisedControllerMergesGroupScopes()
	{
		$reviser = new ControllerReviser;
		$route = $reviser->revise(new Route('GET', '/', ['controller' => 'WildcardScopeControllerStub@index', 'scopes' => 'baz']));
		$this->assertEquals(['baz', 'foo', 'bar'], $route->getAction()['scopes']);
	}


	public function testRoutingToRevisedControllerWithProtectedMethod()
	{
		$reviser = new ControllerReviser;
		$route = $reviser->revise(new Route('GET', '/', ['controller' => 'ProtectedControllerStub@index']));
		$this->assertTrue($route->getAction()['protected']);
	}


	public function testRoutingToRevisedControllerWithUnprotectedMethod()
	{
		$reviser = new ControllerReviser;
		$route = $reviser->revise(new Route('GET', '/', ['controller' => 'UnprotectedControllerStub@index']));
		$this->assertFalse($route->getAction()['protected']);
	}


}
