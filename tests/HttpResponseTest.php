<?php

use Mockery as m;
use Dingo\Api\Http\Response;

class HttpResponseTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->formatter = m::mock('Dingo\Api\Http\ResponseFormat\JsonResponseFormat');
		$this->formatter->shouldReceive('getContentType')->andReturn('foo');
		
		Response::setFormatters(['json' => $this->formatter]);
		Response::setTransformer(m::mock('Dingo\Api\Transformer\Transformer')->shouldReceive('transformableResponse')->andReturn(false)->getMock());
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


	public function testMorphingOther()
	{
		$this->formatter->shouldReceive('formatOther')->once();

		(new Response('foo'))->morph();
	}


	public function testMorphingArrayableInterface()
	{
		$this->formatter->shouldReceive('formatArray')->once()->andReturn('test');

		(new Response(['foo' => 'bar']))->morph();
	}


	/**
	 * @expectedException \RuntimeException
	 */
	public function testGettingUnregisteredFormatterThrowsException()
	{
		Response::getFormatter('test');
	}


}
