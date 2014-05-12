<?php

use Mockery as m;

class TransformerTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->fractal = new League\Fractal\Manager;
		$this->container = m::mock('Illuminate\Container\Container');
		$this->transformerFactory = new Dingo\Api\Transformer\Factory($this->container);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testRegisterTransformer()
	{
		$this->transformerFactory->transform('Foo', 'Bar');
		$this->assertEquals(['Foo' => 'Bar'], $this->transformerFactory->getTransformerBindings());
	}


	public function testDeterminingIfResponseIsTransformable()
	{
		$this->assertFalse($this->transformerFactory->transformableResponse(['foo' => 'bar']), 'Testing that an array is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse(new stdClass), 'Testing that a class with no binding is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse('Foo'), 'Testing that a string with no binding is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse(1), 'Testing that an integer is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse(true), 'Testing that a boolean is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse(false), 'Testing that a boolean is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse(31.1), 'Testing that a float is not transformable.');
		$this->assertFalse($this->transformerFactory->transformableResponse(new Illuminate\Support\Collection([new Foo, new Foo])), 'Testing that a collection with instances that have no binding are not transformable.');
		$this->transformerFactory->transform('Foo', 'FooTransformerStub');
		$this->assertTrue($this->transformerFactory->transformableResponse('Foo'), 'Testing that a string with a binding is transformable.');
		$this->assertTrue($this->transformerFactory->transformableResponse(new Bar), 'Testing that an instance that is bound by a contract is transformable.');
		$this->assertTrue($this->transformerFactory->transformableResponse(new Illuminate\Support\Collection([new Bar, new Bar])), 'Testing that a collection with instances bound by a contract are transformable.');
		$this->assertTrue($this->transformerFactory->transformableResponse(new Illuminate\Support\Collection([new Foo, new Foo])), 'Testing that a collection with instances that have a binding are transformable.');
	}


	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Unable to find bound transformer for "Foo" class.
	 */
	public function testNonexistentTransformerThrowsException()
	{
		$this->transformerFactory->transformResponse(new Foo);
	}


	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Request cannot be set when no transformer has been registered.
	 */
	public function testSettingRequestWithoutRegisteredTransformerThrowsException()
	{
		$this->transformerFactory->setRequest(new Illuminate\Http\Request);
	}


}
