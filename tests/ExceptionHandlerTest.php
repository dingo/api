<?php

class ExceptionHandlerTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->exceptionHandler = new Dingo\Api\ExceptionHandler;
	}


	public function testRegisterExceptionHandler()
	{
		$this->exceptionHandler->register(function(StubHttpException $e){});
		$this->assertArrayHasKey('StubHttpException', $this->exceptionHandler->getHandlers());
	}


	public function testExceptionHandlerWillHandleExceptionPasses()
	{
		$this->exceptionHandler->register(function(StubHttpException $e){});
		$this->assertTrue($this->exceptionHandler->willHandle(new StubHttpException(404)));
	}


	public function testExceptionHandlerWillHandleExceptionFails()
	{
		$this->assertFalse($this->exceptionHandler->willHandle(new StubHttpException(404)));
	}


	public function testExceptionHandlerHandlesException()
	{
		$this->exceptionHandler->register(function(StubHttpException $e)
		{
			return new Illuminate\Http\Response('foo', 404);
		});

		$response = $this->exceptionHandler->handle(new StubHttpException(404, 'bar'));

		$this->assertEquals('foo', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandlerHandlesExceptionAndCreatesNewResponse()
	{
		$this->exceptionHandler->register(function(StubHttpException $e)
		{
			return 'foo';
		});

		$response = $this->exceptionHandler->handle(new StubHttpException(404, 'bar'));

		$this->assertInstanceOf('Illuminate\Http\Response', $response);
		$this->assertEquals('foo', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandlerReturnsNullWhenNoMatchingHandler()
	{
		$this->assertNull($this->exceptionHandler->handle(new StubHttpException(404, 'bar')));
	}


}
