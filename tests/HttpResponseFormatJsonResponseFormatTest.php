<?php

use Mockery as m;
use Dingo\Api\Http\Response;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;

class HttpResponseFormatJsonResponseFormatTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		Response::setFormatters(['json' => new JsonResponseFormat]);
		Response::setTransformer(m::mock('Dingo\Api\Transformer\Transformer')->shouldReceive('transformableResponse')->andReturn(false)->getMock());
	}


	public function tearDown()
	{
		m::close();

		Response::setFormatters([]);
	}


	public function testMorphingEloquentModel()
	{
		$response = with(new Response(new EloquentModelStub))->morph();

		$this->assertEquals('{"user":{"foo":"bar"}}', $response->getContent());
	}


	public function testMorphingEloquentCollection()
	{
		$response = with(new Response(new EloquentCollectionStub))->morph();

		$this->assertEquals('{"users":[{"foo":"bar"},{"foo":"bar"}]}', $response->getContent());
	}


	public function testMorphingEmptyEloquentCollection()
	{
		$response = with(new Response(new EmptyEloquentCollectionStub))->morph();

		$this->assertEquals('[]', $response->getContent());
	}


	public function testMorphingString()
	{
		$response = with(new Response('foo'))->morph();

		$this->assertEquals('foo', $response->getContent());
	}


	public function testMorphingArrayableInterface()
	{
		$messages = new Illuminate\Support\MessageBag(['foo' => 'bar']);

		$response = with(new Response(['foo' => 'bar', 'baz' => $messages]))->morph();

		$this->assertEquals('{"foo":"bar","baz":{"foo":["bar"]}}', $response->getContent());
	}


	public function testMorphingUnknownType()
	{
		$this->assertEquals(1, with(new Response(1))->morph()->getContent());
	}

}
