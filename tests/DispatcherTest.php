<?php

use Mockery as m;
use Dingo\Api\Http\Response;
use Illuminate\Container\Container;
use League\Fractal\Manager as Fractal;

class DispatcherTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->router = $this->setUpRouter();
		$this->request = new Illuminate\Http\Request;
		$this->shield = m::mock('Dingo\Api\Auth\Shield');
		$this->url = new Illuminate\Routing\UrlGenerator($this->router->getRoutes(), $this->request);
		$this->dispatcher = new Dingo\Api\Dispatcher($this->request, $this->url, $this->router, $this->shield);

		Response::setFormatters(['json' => new Dingo\Api\Http\ResponseFormat\JsonResponseFormat]);
		Response::setTransformer($transformer = m::mock('Dingo\Api\Transformer'));
		$transformer->shouldReceive('transformableResponse')->andReturn(false);
	}

	public function setUpRouter()
	{
		$router = new Dingo\Api\Routing\Router(new Illuminate\Events\Dispatcher);
		$router->setDefaultVersion('v1');
		$router->setVendor('test');

		return $router;
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

		$this->assertEquals('test', $this->dispatcher->version('v1')->with(['foo' => 'bar'])->get('test'));
	}


	public function testInternalRequestWithPrefix()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'baz'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
		});

		$this->assertEquals('test', $this->dispatcher->get('test'));
	}


	public function testInternalRequestWithDomain()
	{
		$this->router->api(['version' => 'v1', 'domain' => 'foo.bar'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
		});

		$this->assertEquals('test', $this->dispatcher->get('test'));
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function testInternalRequestThrowsException()
	{
		$this->router->api(['version' => 'v1'], function() {});

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

		$this->shield->shouldReceive('setUser')->once()->with($user);

		$this->dispatcher->be($user);
	}


	public function testPretendingToBeUserForSingleRequest()
	{
		$user = m::mock('Illuminate\Database\Eloquent\Model');

		$this->shield->shouldReceive('setUser')->once()->with($user);
		$this->shield->shouldReceive('setUser')->once()->with(null);

		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('test', function(){ return 'test'; });
		});

		$this->dispatcher->be($user)->once()->get('test');
	}


	public function testInternalRequestUsingRouteName()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('test', ['as' => 'test', function()
			{
				return 'foo';
			}]);

			$this->router->get('test/{foo}', ['as' => 'parameters', function($parameter)
			{
				return $parameter;
			}]);
		});

		$this->assertEquals('foo', $this->dispatcher->route('test'));
		$this->assertEquals('bar', $this->dispatcher->route('parameters', 'bar'));
	}


	public function testInternalRequestUsingControllerAction()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', 'InternalControllerActionRoutingStub@index');
		});

		$this->assertEquals('foo', $this->dispatcher->action('InternalControllerActionRoutingStub@index'));
	}


	public function testInternalRequestUsingRouteNameAndControllerActionDoesNotDoublePrefix()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', ['as' => 'foo', function() { return 'foo'; }]);
			$this->router->get('bar', 'InternalControllerActionRoutingStub@index');
		});

		$this->assertEquals('foo', $this->dispatcher->route('foo'));
		$this->assertEquals('foo', $this->dispatcher->action('InternalControllerActionRoutingStub@index'));
	}


	public function testInternalRequestWithMultipleVersionsCallsCorrectVersion()
	{
		$this->router->api(['version' => 'v1'], function()
		{
			$this->router->get('foo', function() { return 'foo'; });
		});

		$this->router->api(['version' => 'v2'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$this->assertEquals('bar', $this->dispatcher->version('v2')->get('foo'));
	}


	public function testInternalRequestWithPrefixAndNestedInternalRequest()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'foo'; });
		});

		$this->router->api(['version' => 'v2', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'bar'; });
		});

		$this->router->api(['version' => 'v3', 'prefix' => 'api'], function()
		{
			$this->router->get('foo', function() { return 'baz'.$this->dispatcher->version('v2')->get('foo'); });
		});

		$this->assertEquals('bazbar', $this->dispatcher->version('v3')->get('foo'));
	}
	

}
