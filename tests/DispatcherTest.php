<?php

use Mockery as m;
use Dingo\Api\Dispatcher;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Transformer\Factory;
use Illuminate\Container\Container;
use Illuminate\Routing\UrlGenerator;
use League\Fractal\Manager as Fractal;
use Illuminate\Routing\RouteCollection;
use Dingo\Api\Transformer\FractalTransformer;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;

class DispatcherTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->auth = m::mock('Dingo\Api\Auth\Shield');

		$this->router = new Router(new EventsDispatcher);
		$this->router->setDefaultVersion('v1');
		$this->router->setVendor('testing');

		$this->dispatcher = new Dispatcher(new Request, new UrlGenerator(new RouteCollection, new Request), $this->router, $this->auth);

		Response::setFormatters(['json' => new JsonResponseFormat]);
		Response::setTransformer(m::mock('Dingo\Api\Transformer\Transformer')->shouldReceive('transformableResponse')->andReturn(false)->getMock()->shouldReceive('setRequest')->getMock());
	}


	public function tearDown()
	{
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

		$this->auth->shouldReceive('setUser')->once()->with($user);

		$this->dispatcher->be($user);
	}


	public function testPretendingToBeUserForSingleRequest()
	{
		$user = m::mock('Illuminate\Database\Eloquent\Model');

		$this->auth->shouldReceive('setUser')->once()->with($user);
		$this->auth->shouldReceive('setUser')->once()->with(null);

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


	public function testInternalRequestWithNestedInternalRequest()
	{
		$this->dispatcher = new Dispatcher(new Request, m::mock('Illuminate\Routing\UrlGenerator'), $this->router, m::mock('Dingo\Api\Auth\Shield'));

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


	public function testRequestStackIsMaintained()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->post('baz', function()
			{
				$this->assertEquals('bazinga', $this->router->getCurrentRequest()->input('foo'));
			});

			$this->router->post('bar', function()
			{
				$this->assertEquals('baz', $this->router->getCurrentRequest()->input('foo'));
				$this->dispatcher->with(['foo' => 'bazinga'])->post('baz');
				$this->assertEquals('baz', $this->router->getCurrentRequest()->input('foo'));
			});

			$this->router->post('foo', function()
			{
				$this->assertEquals('bar', $this->router->getCurrentRequest()->input('foo'));
				$this->dispatcher->with(['foo' => 'baz'])->post('bar');
				$this->assertEquals('bar', $this->router->getCurrentRequest()->input('foo'));
			});
		});
		
		$this->dispatcher->with(['foo' => 'bar'])->post('foo');
	}


	public function testRouteStackIsMaintained()
	{
		$this->router->api(['version' => 'v1', 'prefix' => 'api'], function()
		{
			$this->router->post('baz', ['as' => 'bazinga', function()
			{
				$this->assertEquals('bazinga', $this->router->currentRouteName());
			}]);

			$this->router->post('bar', ['as' => 'bar', function()
			{
				$this->assertEquals('bar', $this->router->currentRouteName());
				$this->dispatcher->post('baz');
				$this->assertEquals('bar', $this->router->currentRouteName());
			}]);

			$this->router->post('foo', ['as' => 'foo', function()
			{
				$this->assertEquals('foo', $this->router->currentRouteName());
				$this->dispatcher->post('bar');
				$this->assertEquals('foo', $this->router->currentRouteName());
			}]);
		});
		
		$this->dispatcher->post('foo');
	}

	

}
