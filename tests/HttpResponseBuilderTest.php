<?php

use Mockery as m;
use Illuminate\Http\Request;
use Dingo\Api\Http\ResponseBuilder;
use League\Fractal\Manager as Fractal;
use Dingo\Api\Transformer\FractalTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class HttpResponseBuilderTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$fractal = new FractalTransformer(new Fractal);
		$fractal->setRequest(Request::create('GET', 'foo'));
		$this->builder = new ResponseBuilder($fractal);
	}


	public function testBuildArrayResponse()
	{
		$response = $this->builder->withArray(['foo' => 'bar']);
		$this->assertEquals(['foo' => 'bar'], $response->getOriginalContent());
	}


	public function testBuildCollectionResponse()
	{
		$response = $this->builder->withCollection([new Foo, new Foo], new FooTransformerStub);
		$this->assertEquals(['data' => [['foo' => 'bar'], ['foo' => 'bar']]], $response->getOriginalContent());
	}


	public function testBuildItemResponse()
	{
		$response = $this->builder->withItem(new Foo, new FooTransformerStub);
		$this->assertEquals(['data' => ['foo' => 'bar']], $response->getOriginalContent());
	}


	public function testBuildPaginatorResponse()
	{
		$paginator = m::mock('Illuminate\Pagination\Paginator');
		$paginator->shouldReceive('getFactory')->once()->andReturn($factory = m::mock('Illuminate\Pagination\Factory'));
		$paginator->shouldReceive('getItems')->once()->andReturn([new Foo]);
		$paginator->shouldReceive('getTotal')->once()->andReturn(1);
		$paginator->shouldReceive('getPerPage')->once()->andReturn(1);
		$factory->shouldReceive('getCurrentPage')->once()->andReturn(1);

		$response = $this->builder->withPaginator(new IlluminatePaginatorAdapter($paginator), new FooTransformerStub);
		$this->assertEquals([
			'data' => [
				['foo' => 'bar']
			],
			'meta' => [
				'pagination' => [
					'total' => 1,
					'count' => 1,
					'per_page' => 1,
					'current_page' => 1,
					'total_pages' => 1,
					'links' => []
				]
			]
		], $response->getOriginalContent());
	}


	public function testBuildResponseWithDifferentStatusCode()
	{
		$response = $this->builder->setStatusCode(201)->withArray([]);
		$this->assertEquals(201, $response->getStatusCode());
	}


	public function testBuildResponseWithDifferentHeaders()
	{
		$response = $this->builder->addHeader('foo', 'bar')->withArray([]);
		$this->assertEquals('bar', $response->headers->get('foo'));
		
		$response = $this->builder->addHeaders(['foo' => 'baz', 'yin' => 'yang'])->withArray([]);
		$this->assertEquals('baz', $response->headers->get('foo'));
		$this->assertEquals('yang', $response->headers->get('yin'));
	}


	public function testErrorNotFound()
	{
		$response = $this->builder->errorNotFound();
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('{"status_code":404,"error":"Not Found"}', $response->getContent());
	}


	public function testErrorBadRequest()
	{
		$response = $this->builder->errorBadRequest();
		$this->assertEquals(400, $response->getStatusCode());
		$this->assertEquals('{"status_code":400,"error":"Bad Request"}', $response->getContent());
	}


	public function testErrorForbidden()
	{
		$response = $this->builder->errorForbidden();
		$this->assertEquals(403, $response->getStatusCode());
		$this->assertEquals('{"status_code":403,"error":"Forbidden"}', $response->getContent());
	}


	public function testErrorInternal()
	{
		$response = $this->builder->errorInternal();
		$this->assertEquals(500, $response->getStatusCode());
		$this->assertEquals('{"status_code":500,"error":"Internal Error"}', $response->getContent());
	}


	public function testErrorUnauthorized()
	{
		$response = $this->builder->errorUnauthorized();
		$this->assertEquals(401, $response->getStatusCode());
		$this->assertEquals('{"status_code":401,"error":"Unauthorized"}', $response->getContent());
	}


}
