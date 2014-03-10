<?php

use Mockery as m;

class RoutingRouterTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->api = m::mock('Dingo\Api\Api');
		$this->router = new Dingo\Api\Routing\Router(new Illuminate\Events\Dispatcher);
		$this->router->setApi($this->api);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testRegisteringApiRouteGroup()
	{
		$this->api->shouldReceive('currentRequestHandlesVersion')->with('v1')->andReturn(true);
		$this->api->shouldReceive('setRequestOptions')->with([])->andReturn(true);
		$this->api->shouldReceive('currentRequestTargettingApi')->andReturn(true);

		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});
		
		$this->assertEquals('{"message":"bar"}', $this->router->dispatch(Illuminate\Http\Request::create('foo', 'GET'))->getContent());
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
		$this->api->shouldReceive('currentRequestHandlesVersion')->with('v1')->andReturn(true);
		$this->api->shouldReceive('setRequestOptions')->with([])->andReturn(true);
		$this->api->shouldReceive('currentRequestTargettingApi')->andReturn(true);

		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});
		
		$this->assertEquals('{"message":"bar"}', $this->router->dispatch(Dingo\Api\Http\InternalRequest::create('foo', 'GET'))->getContent());
	}


	public function testRouterCatchesHttpExceptionsAndCreatesResponse()
	{
		$this->api->shouldReceive('currentRequestHandlesVersion')->with('v1')->andReturn(true);
		$this->api->shouldReceive('setRequestOptions')->with([])->andReturn(true);
		$this->api->shouldReceive('currentRequestTargettingApi')->andReturn(true);

		$exception = new Symfony\Component\HttpKernel\Exception\HttpException(404);

		$this->api->shouldReceive('handleException')->with($exception)->andReturn(new Illuminate\Http\Response('test', 404));

		$this->router->api(['version' => 'v1'], function() use ($exception)
		{
			$this->router->get('foo', function() use ($exception) { throw $exception; });
		});

		$response = $this->router->dispatch(Illuminate\Http\Request::create('foo', 'GET'));
		
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertEquals('{"message":"test"}', $response->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testRouterCatchesHttpExceptionsAndRethrowsForInternalRequest()
	{
		$this->api->shouldReceive('currentRequestHandlesVersion')->with('v1')->andReturn(true);
		$this->api->shouldReceive('setRequestOptions')->with([])->andReturn(true);
		$this->api->shouldReceive('currentRequestTargettingApi')->andReturn(true);

		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { throw new Symfony\Component\HttpKernel\Exception\HttpException(404); });
		});

		$this->router->dispatch(Dingo\Api\Http\InternalRequest::create('foo', 'GET'));
	}


}