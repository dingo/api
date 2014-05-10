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
		$this->assertFalse($this->transformerFactory->transformableResponse(['foo' => 'bar']));
		$this->assertFalse($this->transformerFactory->transformableResponse(new stdClass));
		$this->assertFalse($this->transformerFactory->transformableResponse('Foo'));
		$this->assertFalse($this->transformerFactory->transformableResponse(1));
		$this->assertFalse($this->transformerFactory->transformableResponse(true));
		$this->assertFalse($this->transformerFactory->transformableResponse(false));
		$this->assertFalse($this->transformerFactory->transformableResponse(31.1));
		$this->assertFalse($this->transformerFactory->transformableResponse(new Illuminate\Support\Collection([new Foo, new Foo])));
		$this->transformerFactory->transform('Foo', 'FooTransformerStub');
		$this->assertTrue($this->transformerFactory->transformableResponse('Foo'));
		$this->assertTrue($this->transformerFactory->transformableResponse(new Bar));
		$this->assertTrue($this->transformerFactory->transformableResponse(new Illuminate\Support\Collection([new Foo, new Foo])));
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
