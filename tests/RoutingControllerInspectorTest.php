<?php

use Dingo\Api\Routing\ControllerInspector;

class RoutingControllerInspectorTest extends PHPUnit_Framework_TestCase {


	public function testControllerMethodIsNotRoutable()
	{
		$method = new ReflectionMethod('Dingo\Api\Routing\Controller', 'scope');
		$inspector = new ControllerInspector;
		$this->assertFalse($inspector->isRoutable($method, 'Dingo\Api\Routing\Controller'));
	}


	public function testControllerMethodIsRoutable()
	{
		$method = new ReflectionMethod('ControllerStub', 'getFoo');
		$inspector = new ControllerInspector;
		$this->assertTrue($inspector->isRoutable($method, 'ControllerStub'));
	}


}

class ControllerStub extends Dingo\Api\Routing\Controller {

	public function getFoo() {}

}
