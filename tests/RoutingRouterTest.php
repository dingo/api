<?php

use Mockery as m;

class RoutingRouterTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->exceptionHandler = m::mock('Dingo\Api\ExceptionHandler');
		$this->router = new Dingo\Api\Routing\Router(new Illuminate\Events\Dispatcher);
		$this->router->setExceptionHandler($this->exceptionHandler);
		$this->router->setDefaultApiVersion('v1');
		$this->router->setApiVendor('testing');

		$this->router->filter('api', function()
		{
			$this->router->enableApiRouting();
		});
	}


	public function tearDown()
	{
		m::close();
	}


	public function testRegisteringApiRouteGroup()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$request = Illuminate\Http\Request::create('foo', 'GET');
		$request->headers->set('accept', 'application/vnd.testing.v1+json');
		
		$this->assertEquals('{"message":"bar"}', $this->router->dispatch($request)->getContent());
	}


	/**
	 * @expectedException BadMethodCallException
	 */
	public function testRegisteringApiRouteGroupWithoutVersionThrowsException()
	{
		$this->router->api([], function(){});
	}


	public function testRouterDispatchesInternalRequests()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});
		
		$this->assertEquals('{"message":"bar"}', $this->router->dispatch(Dingo\Api\Http\InternalRequest::create('foo', 'GET'))->getContent());
	}


	public function testAddingRouteFallsThroughToRouterCollection()
	{
		$this->router->get('foo', function() { return 'bar'; });

		$this->assertCount(1, $this->router->getRoutes());
	}


	public function testDispatchingRequestTargetsApiButFailsToFindRouteFallsThroughToRouterCollection()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function() {});

		$this->router->get('foo', function() { return 'bar'; });

		$this->assertEquals('bar', $this->router->dispatch(Illuminate\Http\Request::create('foo', 'GET'))->getContent());
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testGettingUnkownApiCollectionThrowsException()
	{
		$this->router->getApiCollection('v1');
	}


	public function testRouterCatchesHttpExceptionsAndCreatesResponse()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$this->router->api(['version' => 'v1'], function() use ($exception)
		{
			$this->router->get('foo', function() use ($exception) { throw $exception; });
		});

		$response = $this->router->dispatch(Illuminate\Http\Request::create('foo', 'GET'));
		
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('{"message":"404 Not Found"}', $response->getContent());
	}


	public function testExceptionHandledAndResponseIsReturned()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404, 'testing');

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->router->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('testing', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandledAndResponseIsReturnedWithMissingMessage()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->router->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('404 Not Found', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testExceptionHandledAndResponseIsReturnedUsingResourceException()
	{
		$exception = new Dingo\Api\Exception\ResourceException('testing', ['foo' => 'bar']);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(false);

		$response = $this->router->handleException($exception);
		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('{"message":"testing","errors":{}}', $response->getContent());
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $response->getOriginalContent()['errors']);
		$this->assertEquals(422, $response->getStatusCode());
	}


	public function testExceptionHandledByExceptionHandler()
	{
		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->exceptionHandler->shouldReceive('willHandle')->with($exception)->andReturn(true);
		$this->exceptionHandler->shouldReceive('handle')->with($exception)->andReturn(new Dingo\Api\Http\Response('testing', 404));

		$response = $this->router->handleException($exception);

		$this->assertInstanceOf('Dingo\Api\Http\Response', $response);
		$this->assertEquals('testing', $response->getContent());
		$this->assertEquals(404, $response->getStatusCode());
	}



	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testRouterCatchesHttpExceptionsAndRethrowsForInternalRequest()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { throw new Symfony\Component\HttpKernel\Exception\HttpException(404); });
		});

		$this->router->dispatch(Dingo\Api\Http\InternalRequest::create('foo', 'GET'));
	}


}