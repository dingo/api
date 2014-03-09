<?php

use Mockery as m;

class DispatcherTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->request = m::mock('Illuminate\Http\Request');
		$this->router = m::mock('Dingo\Api\Routing\Router');
		$this->dispatcher = new Dingo\Api\Dispatcher($this->request, $this->router, 'testing');
	}


	public function tearDown()
	{
		m::close();
	}


	public function testRegisterApiGroupWithoutDomainOrPrefix()
	{
		$this->router->shouldReceive('enableApi')->once();
		$this->router->shouldReceive('disableApi')->once();
		$this->request->shouldReceive('header')->once()->with('accept')->andReturn('application/vnd.testing.v1+json');

		$group = function(){};

		$this->router->shouldReceive('group')->with([], $group);

		$this->dispatcher->group('v1', $group);
	}


	public function testRegisterApiGroupWithDomainAndPrefix()
	{
		$this->router->shouldReceive('enableApi')->once();
		$this->router->shouldReceive('disableApi')->once();
		$this->request->shouldReceive('header')->once()->with('accept')->andReturn('application/vnd.testing.v1+json');

		$group = function(){};

		$this->router->shouldReceive('group')->with(['domain' => 'testing', 'prefix' => 'testing'], $group);

		$this->dispatcher->domain('testing')->prefix('testing')->group('v1', $group);
	}


	public function testRegisterApiGroupWhenNoVersionSentWithAcceptHeader()
	{
		$this->router->shouldReceive('enableApi')->once();
		$this->router->shouldReceive('disableApi')->once();
		$this->request->shouldReceive('header')->once()->with('accept')->andReturn('application/vnd.testing+json');

		$group = function(){};

		$this->router->shouldReceive('group')->with([], $group);

		$this->dispatcher->defaultsTo('v1');
		$this->dispatcher->group('v1', $group);
	}


	public function testInternalRequests()
	{
		$request = new Illuminate\Http\Request;
		$dispatcher = new Dingo\Api\Dispatcher($request, $this->router, 'testing');

		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->assertEquals('test', $dispatcher->get('test'));
		$this->assertEquals('test', $dispatcher->post('test'));
		$this->assertEquals('test', $dispatcher->put('test'));
		$this->assertEquals('test', $dispatcher->patch('test'));
		$this->assertEquals('test', $dispatcher->head('test'));
		$this->assertEquals('test', $dispatcher->delete('test'));
	}


	public function testInternalRequestWithVersionAndParameters()
	{
		$request = new Illuminate\Http\Request;
		$dispatcher = new Dingo\Api\Dispatcher($request, $this->router, 'testing');

		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$this->assertEquals('test', $dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
	}


	public function testInternalRequestWithPrefixAndDomain()
	{
		$request = new Illuminate\Http\Request;
		$dispatcher = new Dingo\Api\Dispatcher($request, $this->router, 'testing');

		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 200));

		$dispatcher->domain('testing')->prefix('testing');

		$this->assertEquals('test', $dispatcher->get('test'));
	}


	/**
	 * @expectedException Dingo\Api\ApiException
	 */
	public function testInternalRequestThrowsException()
	{
		$request = new Illuminate\Http\Request;
		$dispatcher = new Dingo\Api\Dispatcher($request, $this->router, 'testing');

		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response('test', 500));

		$dispatcher->get('test');
	}


	public function testInternalRequestThrowsExceptionAndBuildsErrors()
	{
		$request = new Illuminate\Http\Request;
		$dispatcher = new Dingo\Api\Dispatcher($request, $this->router, 'testing');

		$this->router->shouldReceive('dispatch')->andReturn(new Dingo\Api\Http\Response([
			'message' => 'test',
			'errors' => new Illuminate\Support\MessageBag
		], 500));

		try
		{
			$dispatcher->get('test');
		}
		catch (Dingo\Api\ApiException $exception)
		{
			$this->assertInstanceOf('Illuminate\Support\MessageBag', $exception->getErrors());
			$this->assertEquals(500, $exception->getStatusCode());
			$this->assertEquals('test', $exception->getMessage());

			return;
		}

		$this->fail('Expected Dingo\Api\ApiException to be thrown.');
	}


}