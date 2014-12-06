<?php

namespace Dingo\Api\Tests\Routing;

use ReflectionMethod;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Routing\ControllerInspector;

class ControllerInspectorTest extends PHPUnit_Framework_TestCase
{
    public function testControllerTraitGetPropertiesMethodIsNotRoutable()
    {
        $method = new ReflectionMethod('Dingo\Api\Tests\Stubs\ControllerStub', 'getProperties');
        $inspector = new ControllerInspector;
        $this->assertFalse($inspector->isRoutable($method, 'Dingo\Api\Tests\Stubs\ControllerStub'));
    }

    public function testControllerMethodIsRoutable()
    {
        $method = new ReflectionMethod('Dingo\Api\Tests\Stubs\ControllerStub', 'getIndex');
        $inspector = new ControllerInspector;
        $this->assertTrue($inspector->isRoutable($method, 'Dingo\Api\Tests\Stubs\ControllerStub'));
    }
}
