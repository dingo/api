<?php

use Mockery as m;

class DispatcherTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->request = new Illuminate\Http\Request;
		$this->router = m::mock('Dingo\Api\Routing\Router');
		$this->dispatcher = new Dingo\Api\Dispatcher($this->request, $this->router);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testInternalRequests()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->router->shouldReceive('getDefaultApiVersion')->times(6)->andReturn('v1');
		$this->router->shouldReceive('getApiVendor')->times(6)->andReturn('testing');
		$this->router->shouldReceive('getApiCollection')->times(6)->with('v1')->andReturn(new Dingo\Api\Routing\ApiCollection('v1', ['prefix' => 'api']));
		$this->router->shouldReceive('enableApiRouting')->times(6);

		$this->assertEquals('test', $this->dispatcher->get('test'));
		$this->assertEquals('test', $this->dispatcher->post('test'));
		$this->assertEquals('test', $this->dispatcher->put('test'));
		$this->assertEquals('test', $this->dispatcher->patch('test'));
		$this->assertEquals('test', $this->dispatcher->head('test'));
		$this->assertEquals('test', $this->dispatcher->delete('test'));
	}


	public function testInternalRequestWithVersionAndParameters()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->router->shouldReceive('getApiVendor')->once()->andReturn('testing');
		$this->router->shouldReceive('getApiCollection')->once()->with('v1')->andReturn(new Dingo\Api\Routing\ApiCollection('v1', ['prefix' => 'api']));
		$this->router->shouldReceive('enableApiRouting')->once();

		$this->assertEquals('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
	}


	public function testInternalRequestWithPrefixAndDomain()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->router->shouldReceive('getDefaultApiVersion')->once()->andReturn('v1');
		$this->router->shouldReceive('getApiVendor')->once()->andReturn('testing');
		$this->router->shouldReceive('getApiCollection')->once()->with('v1')->andReturn(new Dingo\Api\Routing\ApiCollection('v1', ['domain' => 'testing']));
		$this->router->shouldReceive('enableApiRouting')->once();

		$this->assertEquals('test', $this->dispatcher->get('test'));
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testInternalRequestThrowsException()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 500));

		$this->router->shouldReceive('getDefaultApiVersion')->once()->andReturn('v1');
		$this->router->shouldReceive('getApiVendor')->once()->andReturn('testing');
		$this->router->shouldReceive('getApiCollection')->once()->with('v1')->andReturn(new Dingo\Api\Routing\ApiCollection('v1', ['domain' => 'testing']));
		$this->router->shouldReceive('enableApiRouting')->once();

		$this->dispatcher->get('test');
	}
	

}