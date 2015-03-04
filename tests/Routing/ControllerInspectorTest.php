<?php

namespace Dingo\Api\tests\Routing;

use Dingo\Api\Routing\ControllerInspector;
use PHPUnit_Framework_TestCase;
use ReflectionMethod;

class ControllerInspectorTest extends PHPUnit_Framework_TestCase
{
    public function testControllerTraitGetPropertiesMethodIsNotRoutable()
    {
        $method = new ReflectionMethod('Dingo\Api\Tests\Stubs\ControllerStub', 'getProperties');
        $inspector = new ControllerInspector();
        $this->assertFalse($inspector->isRoutable($method, 'Dingo\Api\Tests\Stubs\ControllerStub'));
    }

    public function testControllerMethodIsRoutable()
    {
        $method = new ReflectionMethod('Dingo\Api\Tests\Stubs\ControllerStub', 'getIndex');
        $inspector = new ControllerInspector();
        $this->assertTrue($inspector->isRoutable($method, 'Dingo\Api\Tests\Stubs\ControllerStub'));
    }
}
