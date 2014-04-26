<?php

use Mockery as m;
use Dingo\Api\Dispatcher;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;

class DispatcherTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->request = new Request;
		$this->router = new Router(new EventsDispatcher);
		$this->router->setDefaultVersion('v1');
		$this->router->setVendor('test');
		$this->auth = m::mock('Dingo\Api\Authentication');
		$this->dispatcher = new Dispatcher($this->request, $this->router, $this->auth);

		Response::setFormatters(['json' => new JsonResponseFormat]);
	}


	public function tearDown()
	{
		Response::setFormatters([]);

		m::close();
	}


	public function testInternalRequests()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
			$this->router->post('test', function(){ return 'test'; });
			$this->router->put('test', function(){ return 'test'; });
			$this->router->patch('test', function(){ return 'test'; });
			$this->router->delete('test', function(){ return 'test'; });
		});

		$this->auth->shouldReceive('setUser')->times(5)->with(null);

		$this->assertEquals('test', $this->dispatcher->get('test'));
		$this->assertEquals('test', $this->dispatcher->post('test'));
		$this->assertEquals('test', $this->dispatcher->put('test'));
		$this->assertEquals('test', $this->dispatcher->patch('test'));
		$this->assertEquals('test', $this->dispatcher->delete('test'));
	}


	public function testInternalRequestWithVersionAndParameters()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
		});

		$this->auth->shouldReceive('setUser')->once()->with(null);

		$this->assertEquals('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
	}


	public function testInternalRequestWithPrefix()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'baz'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
		});

		$this->auth->shouldReceive('setUser')->once()->with(null);

		$this->assertEquals('test', $this->dispatcher->get('test'));
	}


	public function testInternalRequestWithDomain()
	{
		$this->router->api(['version' => 'v1', 'domain' => 'foo.bar'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
		});

		$this->auth->shouldReceive('setUser')->once()->with(null);

		$this->assertEquals('test', $this->dispatcher->get('test'));
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testInternalRequestThrowsException()
	{
		$this->router->api(['version' => 'v1'], function() {});

		$this->auth->shouldReceive('setUser')->once()->with(null);

		$this->dispatcher->get('test');
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testInternalRequestThrowsExceptionWhenResponseIsNotOkay()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('test', function()
			{
				return new Illuminate\Http\Response('test', 401);
			});
		});

		$this->auth->shouldReceive('setUser')->once()->with(null);

		$this->dispatcher->get('test');
	}


	/**
	 * @expectedException \RuntimeException
	 */
	public function testPretendingToBeUserWithInvalidParameterThrowsException()
	{
		$this->dispatcher->be('foo');
	}


	public function testPretendingToBeUserSetsUserOnAuthentication()
	{
		$user = m::mock('Illuminate\Database\Eloquent\Model');

		$this->auth->shouldReceive('setUser')->once()->with($user);

		$this->dispatcher->be($user);
	}
	

}
