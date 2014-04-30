<?php

use Mockery as m;

class TransformerTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->fractal = new League\Fractal\Manager;
		$this->container = m::mock('Illuminate\Container\Container');
		$this->transformer = new Dingo\Api\Transformer($this->fractal, $this->container);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testRegisterTransformer()
	{
		$this->transformer->transform('Foo', 'Bar');
		$this->assertEquals(['Foo' => 'Bar'], $this->transformer->getTransformers());
	}


	public function testDeterminingIfResponseIsTransformable()
	{
		$this->assertFalse($this->transformer->transformableResponse('Foo'));
		$this->transformer->transform('Foo', 'Bar');
		$this->assertTrue($this->transformer->transformableResponse('Foo'));
	}


	public function testTransformingResponseUsingTransformerClassName()
	{
		$this->transformer->transform('FooStub', 'FooTransformerStub');
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformer->transformResponse(new FooStub));
	}


	public function testTransformingResponseUsingCallback()
	{
		$this->transformer->transform('FooStub', function()
		{
			return new FooTransformerStub;
		});
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformer->transformResponse(new FooStub));
	}


	public function testTransformingCollectionUsingTransformerClassName()
	{
		$this->transformer->transform('FooStub', 'FooTransformerStub');
		$response = new Illuminate\Support\Collection([new FooStub, new FooStub]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformer->transformResponse($response));
	}


	public function testTransformingCollectionUsingCallback()
	{
		$this->transformer->transform('FooStub', function()
		{
			return new FooTransformerStub;
		});
		$response = new Illuminate\Support\Collection([new FooStub, new FooStub]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformer->transformResponse($response));
	}


}
