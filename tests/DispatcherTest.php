<?php

use Mockery as m;

class DispatcherTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->request = new Illuminate\Http\Request;
		$this->router = m::mock('Dingo\Api\Routing\Router');
		$this->api = m::mock('Dingo\Api\Api');
		$this->dispatcher = new Dingo\Api\Dispatcher($this->request, $this->router, $this->api);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testInternalRequests()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->api->shouldReceive('hasPrefix')->times(6)->andReturn(false);
		$this->api->shouldReceive('hasDomain')->times(6)->andReturn(false);
		$this->api->shouldReceive('getDefaultVersion')->times(6)->andReturn('v1');
		$this->api->shouldReceive('getVendor')->times(6)->andReturn('testing');

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

		$this->api->shouldReceive('hasPrefix')->once()->andReturn(false);
		$this->api->shouldReceive('hasDomain')->once()->andReturn(false);
		$this->api->shouldReceive('getVendor')->once()->andReturn('testing');

		$this->assertEquals('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
	}


	public function testInternalRequestWithPrefixAndDomain()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->api->shouldReceive('hasPrefix')->once()->andReturn(true);
		$this->api->shouldReceive('getPrefix')->once()->andReturn('testing');
		$this->api->shouldReceive('hasDomain')->once()->andReturn(true);
		$this->api->shouldReceive('getDomain')->once()->andReturn('testing');
		$this->api->shouldReceive('getDefaultVersion')->once()->andReturn('v1');
		$this->api->shouldReceive('getVendor')->once()->andReturn('testing');

		$this->assertEquals('test', $this->dispatcher->get('test'));
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testInternalRequestThrowsException()
	{
		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 500));

		$this->api->shouldReceive('hasPrefix')->once()->andReturn(false);
		$this->api->shouldReceive('hasDomain')->once()->andReturn(false);
		$this->api->shouldReceive('getDefaultVersion')->once()->andReturn('v1');
		$this->api->shouldReceive('getVendor')->once()->andReturn('testing');

		$this->dispatcher->get('test');
	}
	

}