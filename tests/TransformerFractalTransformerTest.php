<?php

use Mockery as m;

class TransformerFractalTransformerTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->fractal = new League\Fractal\Manager;
		$this->container = m::mock('Illuminate\Container\Container');
		$this->transformer = new Dingo\Api\Transformer\FractalTransformer($this->fractal);
		$this->transformerFactory = new Dingo\Api\Transformer\Factory($this->container);
		$this->transformerFactory->setTransformer($this->transformer);
		$this->transformerFactory->setRequest(new Illuminate\Http\Request);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testTransformingResponseUsingTransformerClassName()
	{
		$this->transformerFactory->transform('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformerFactory->transformResponse(new Foo));
	}


	public function testTransformingResponseUsingCallback()
	{
		$this->transformerFactory->transform('Foo', function()
		{
			return new FooTransformerStub;
		});
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformerFactory->transformResponse(new Foo));
	}


	public function testTransformingCollectionUsingTransformerClassName()
	{
		$this->transformerFactory->transform('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);
		$response = new Illuminate\Support\Collection([new Foo, new Foo]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformerFactory->transformResponse($response));
	}


	public function testTransformingCollectionUsingCallback()
	{
		$this->transformerFactory->transform('Foo', function()
		{
			return new FooTransformerStub;
		});
		$response = new Illuminate\Support\Collection([new Foo, new Foo]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformerFactory->transformResponse($response));
	}


	public function testTransformingNestedRelationships()
	{
		$this->transformerFactory->setRequest(Illuminate\Http\Request::create('/', 'GET', ['include' => 'foo']));
		$this->assertEquals(['data' => ['bar' => 'baz', 'foo' => ['data' => ['foo' => 'bar']]]], $this->transformerFactory->transformResponse(new Bar));
	}


	public function testTransformingPaginator()
	{
		$this->transformerFactory->transform('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);

		$paginator = m::mock('Illuminate\Pagination\Paginator');
		$environment = m::mock('Illuminate\Pagination\Environment');

		$paginator->shouldReceive('first')->once()->andReturn(new Foo);
		$paginator->shouldReceive('getEnvironment')->once()->andReturn($environment);
		$paginator->shouldReceive('getItems')->once()->andReturn($items = [
			new Foo,
			new Foo
		]);
		$paginator->shouldReceive('getTotal')->once()->andReturn(2);
		$paginator->shouldReceive('getPerPage')->once()->andReturn(1);
		$paginator->shouldReceive('getIterator')->once()->andReturn(new ArrayIterator($items));

		$environment->shouldReceive('getCurrentPage')->once()->andReturn(1);
		$environment->shouldReceive('getPageName')->once()->andReturn('page');
		$environment->shouldReceive('getCurrentUrl')->once()->andReturn('http://foo.bar/');

		$this->assertEquals([
			'data' => [
				['foo' => 'bar'],
				['foo' => 'bar']
			],
			'meta' => [
				'pagination' => [
					'total' => 2,
					'count' => 2,
					'per_page' => 1,
					'current_page' => 1,
					'total_pages' => 2,
					'links' => [
						'next' => 'http://foo.bar/?page=2'
					]
				]
			],
		], $this->transformerFactory->transformResponse($paginator));		
	}


}
