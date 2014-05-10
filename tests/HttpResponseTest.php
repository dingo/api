<?php

use Mockery as m;
use Dingo\Api\Http\Response;

class HttpResponseTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->formatter = m::mock('Dingo\Api\Http\ResponseFormat\JsonResponseFormat');
		$this->formatter->shouldReceive('getContentType')->andReturn('foo');
		
		Response::setFormatters(['json' => $this->formatter]);
		Response::setTransformer(m::mock('Dingo\Api\Transformer\Factory')->shouldReceive('transformableResponse')->andReturn(false)->getMock());
	}


	public function tearDown()
	{
		m::close();

		Response::setFormatters([]);
	}


	public function testMorphingEloquentModel()
	{
		$this->formatter->shouldReceive('formatEloquentModel')->once()->andReturn('test');

		(new Response(new EloquentModelStub))->morph();
	}


	public function testMorphingEloquentCollection()
	{
		$this->formatter->shouldReceive('formatEloquentCollection')->once()->andReturn('test');

		(new Response(new EloquentCollectionStub))->morph();
	}


	public function testMorphingJsonableInterface()
	{
		$this->formatter->shouldReceive('formatJsonableInterface')->once()->andReturn('test');

		(new Response(new JsonableStub))->morph();
	}


	public function testMorphingString()
	{
		$this->formatter->shouldReceive('formatString')->once();

		(new Response('foo'))->morph();
	}


	public function testMorphingArrayableInterface()
	{
		$this->formatter->shouldReceive('formatArrayableInterface')->once()->andReturn('test');

		(new Response(['foo' => 'bar']))->morph();
	}


	public function testMorphingUnknownType()
	{
		$this->formatter->shouldReceive('formatUnknown')->once()->andReturn('test');

		(new Response(1))->morph();
	}


	/**
	 * @expectedException \RuntimeException
	 */
	public function testGettingUnregisteredFormatterThrowsException()
	{
		Response::getFormatter('test');
	}


}
