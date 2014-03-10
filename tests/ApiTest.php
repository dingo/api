<?php

use Mockery as m;

class ApiTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->request = m::mock('Illuminate\Http\Request');
		$this->exceptionHandler = m::mock('Dingo\Api\ExceptionHandler');
		$this->api = new Dingo\Api\Api($this->request, $this->exceptionHandler, 'testing', 'v1');
	}


	public function tearDown()
	{
		m::close();
	}


	public function testCheckingIfCurrentRequestHandlesDefaultVersion()
	{
		$this->request->shouldReceive('header')->with('accept')->andReturn('testing');

		$this->assertTrue($this->api->currentRequestHandlesVersion('v1'));
		$this->assertFalse($this->api->currentRequestHandlesVersion('v2'));
	}


	public function testCheckingIfCurrentRequestHandlesGivenVersion()
	{
		$this->request->shouldReceive('header')->with('accept')->andReturn('application/vnd.testing.v2+json');

		$this->assertFalse($this->api->currentRequestHandlesVersion('v1'));
		$this->assertTrue($this->api->currentRequestHandlesVersion('v2'));
	}


	public function testCheckingIfCurrentRequestTargettingApiWithNoOptionsFails()
	{
		$this->request->shouldReceive('header')->with('host')->andReturn('foo');
		$this->request->shouldReceive('getPathInfo')->andReturn('bar');

		$this->assertFalse($this->api->currentRequestTargettingApi());
	}


	public function testCheckingIfCurrentRequestTargettingApiWithMatchingHostPasses()
	{
		$this->request->shouldReceive('header')->with('host')->andReturn('foo');

		$this->api->setRequestOptions(['domain' => 'foo']);

		$this->assertTrue($this->api->currentRequestTargettingApi());
	}


	public function testCheckingIfCurrentRequestTargettingApiWithMatchingPrefixPasses()
	{
		$this->request->shouldReceive('header')->with('host')->andReturn('foo');
		$this->request->shouldReceive('getPathInfo')->times(3)->andReturn('/bar', '/baz/bar', '/bar/baz/yin');

		$this->api->setRequestOptions(['prefix' => 'bar']);

		$this->assertTrue($this->api->currentRequestTargettingApi());
		$this->assertFalse($this->api->currentRequestTargettingApi());
		$this->assertTrue($this->api->currentRequestTargettingApi());
	}


	public function testExceptionHandledAndResponseIsReturned()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404, 'testing');

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->api->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('testing', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandledAndResponseIsReturnedWithMissingMessage()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->api->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('404 Not Found', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandledAndResponseIsReturnedUsingResourceException()
	{
		$exception = new Dingo\Api\Exception\ResourceException('testing');

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->api->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('["testing",{}]', $response->getContent());
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $response->getOriginalContent()[1]);
		$this->assertEquals(422, $response->getStatusCode());
	}


	public function testExceptionHandledByExceptionHandler()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(true);
		$this->exceptionHandler->shouldReceive('handle')->with($exception)->andReturn(new Dingo\Api\Http\Response('testing', 404));

		$response = $this->api->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('testing', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


}