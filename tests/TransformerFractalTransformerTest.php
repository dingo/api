<?php

use Mockery as m;

class TransformerFractalTransformerTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->fractal = new League\Fractal\Manager;
		$this->container = m::mock('Illuminate\Container\Container');
		$this->transformer = new Dingo\Api\Transformer\FractalTransformer($this->fractal);
		$this->transformer->setRequest(new Illuminate\Http\Request);
		$this->transformer->setContainer($this->container);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testTransformingResponseUsingTransformerClassName()
	{
		$this->transformer->registerBinding('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformer->transform(new Foo));
	}


	public function testTransformingResponseUsingCallback()
	{
		$this->transformer->registerBinding('Foo', function()
		{
			return new FooTransformerStub;
		});
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformer->transform(new Foo));
	}


	public function testTransformingCollectionUsingTransformerClassName()
	{
		$this->transformer->registerBinding('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);
		$response = new Illuminate\Support\Collection([new Foo, new Foo]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformer->transform($response));
	}


	public function testTransformingCollectionUsingCallback()
	{
		$this->transformer->registerBinding('Foo', function()
		{
			return new FooTransformerStub;
		});
		$response = new Illuminate\Support\Collection([new Foo, new Foo]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformer->transform($response));
	}


	public function testTransformingNestedRelationships()
	{
		$this->transformer->setRequest(Illuminate\Http\Request::create('/', 'GET', ['include' => 'foo']));
		$this->assertEquals(['data' => ['bar' => 'baz', 'foo' => ['data' => ['foo' => 'bar']]]], $this->transformer->transform(new Bar));
	}


	public function testTransformingPaginator()
	{
		$this->transformer->registerBinding('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);

		$paginator = m::mock('Illuminate\Pagination\Paginator');
		$environment = m::mock('Illuminate\Pagination\Environment');
		$factory = m::mock('Illuminate\Pagination\Factory');

		$paginator->shouldReceive('first')->once()->andReturn(new Foo);
		$paginator->shouldReceive('getEnvironment')->andReturn($environment);
		$paginator->shouldReceive('getFactory')->andReturn($factory);
		$paginator->shouldReceive('getItems')->once()->andReturn($items = [
			new Foo,
			new Foo
		]);
		$paginator->shouldReceive('getTotal')->once()->andReturn(2);
		$paginator->shouldReceive('getPerPage')->once()->andReturn(1);
		$paginator->shouldReceive('getIterator')->once()->andReturn(new ArrayIterator($items));

		$environment->shouldReceive('getCurrentPage')->andReturn(1);
		$environment->shouldReceive('getPageName')->andReturn('page');
		$environment->shouldReceive('getCurrentUrl')->andReturn('http://foo.bar/');

		$factory->shouldReceive('getCurrentPage')->andReturn(1);
		$factory->shouldReceive('getPageName')->andReturn('page');
		$factory->shouldReceive('getCurrentUrl')->andReturn('http://foo.bar/');

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
		], $this->transformer->transform($paginator));		
	}


}
