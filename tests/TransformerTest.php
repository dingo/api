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
		$this->assertFalse($this->transformer->transformableResponse(['foo' => 'bar']));
		$this->assertFalse($this->transformer->transformableResponse(new stdClass));
		$this->assertFalse($this->transformer->transformableResponse('Foo'));
		$this->assertFalse($this->transformer->transformableResponse(1));
		$this->assertFalse($this->transformer->transformableResponse(true));
		$this->assertFalse($this->transformer->transformableResponse(false));
		$this->assertFalse($this->transformer->transformableResponse(31.1));
		$this->assertFalse($this->transformer->transformableResponse(new Illuminate\Support\Collection([new Foo, new Foo])));
		$this->transformer->transform('Foo', 'Bar');
		$this->assertTrue($this->transformer->transformableResponse('Foo'));
		$this->assertTrue($this->transformer->transformableResponse(new Illuminate\Support\Collection([new Foo, new Foo])));
	}


	public function testTransformingResponseUsingTransformerClassName()
	{
		$this->transformer->transform('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformer->transformResponse(new Foo));
	}


	public function testTransformingResponseUsingCallback()
	{
		$this->transformer->transform('Foo', function()
		{
			return new FooTransformerStub;
		});
		$this->assertEquals(['data' => ['foo' => 'bar']], $this->transformer->transformResponse(new Foo));
	}


	public function testTransformingCollectionUsingTransformerClassName()
	{
		$this->transformer->transform('Foo', 'FooTransformerStub');
		$this->container->shouldReceive('make')->once()->with('FooTransformerStub')->andReturn(new FooTransformerStub);
		$response = new Illuminate\Support\Collection([new Foo, new Foo]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformer->transformResponse($response));
	}


	public function testTransformingCollectionUsingCallback()
	{
		$this->transformer->transform('Foo', function()
		{
			return new FooTransformerStub;
		});
		$response = new Illuminate\Support\Collection([new Foo, new Foo]);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $this->transformer->transformResponse($response));
	}


	public function testTransformingNestedRelationships()
	{
		$this->transformer->transform('Bar', 'BarTransformerStub');
		$this->container->shouldReceive('make')->once()->with('BarTransformerStub')->andReturn(new BarTransformerStub);
		$this->transformer->setRequest(Illuminate\Http\Request::create('/', 'GET', ['embeds' => 'foo']));
		$this->assertEquals(['data' => ['bar' => 'baz', 'foo' => ['data' => ['foo' => 'bar']]], 'embeds' => ['foo']], $this->transformer->transformResponse(new Bar));
	}


	public function testTransformingPaginator()
	{
		$this->transformer->transform('Foo', 'FooTransformerStub');
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
		], $this->transformer->transformResponse($paginator));		
	}


	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Unable to find bound transformer for "Foo" class.
	 */
	public function testNonexistentTransformerThrowsException()
	{
		$this->transformer->transformResponse(new Foo);
	}


}

class FooTransformerStub extends League\Fractal\TransformerAbstract {

	public function transform(Foo $foo)
	{
		return ['foo' => 'bar'];
	}

}

class BarTransformerStub extends League\Fractal\TransformerAbstract {

	protected $availableEmbeds = ['foo'];

	public function transform(Bar $bar)
	{
		return ['bar' => 'baz'];
	}

	public function embedFoo(Bar $bar)
	{
		return $this->item(new Foo, new FooTransformerStub);
	}

}

class Foo {
	
}

class Bar {

}
