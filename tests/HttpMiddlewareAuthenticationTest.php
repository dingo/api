<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Middleware\Authentication;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class HttpMiddlewareAuthenticationTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$this->container = m::mock('Illuminate\Container\Container');
		$this->router = m::mock('Dingo\Api\Routing\Router');
		$this->collection = m::mock('Dingo\Api\Routing\ApiRouteCollection');
		$this->container->shouldReceive('boot')->atLeast()->once();
		$this->router->shouldReceive('requestTargettingApi')->andReturn(true);
		$this->middleware = new Authentication($this->app, $this->container);

		Dingo\Api\Http\Response::setTransformer(m::mock('Dingo\Api\Transformer\Transformer')->shouldReceive('transformableResponse')->andReturn(false)->getMock());
	}


	public function tearDown()
	{
		m::close();
	}


	public function testWrappedKernelIsHandledForInternalRequestsAndAuthenticatedUsers()
	{
		$request = InternalRequest::create('/', 'GET');

		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$this->assertEquals('test', $this->middleware->handle($request)->getContent());

		$request = Request::create('/', 'GET');

		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => true]));
		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testWrappedKernelIsHandledWhenNoApiRouteCollectionForRequest()
	{
		$request = Request::create('/', 'GET');

		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn(null);
		$this->router->shouldReceive('getDefaultApiRouteCollection')->once()->andReturn(null);

		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testWrappedKernelIsHandledWhenRouteNotFoundInApiRouteCollection()
	{
		$request = Request::create('/', 'GET');

		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($request)->andThrow(new NotFoundHttpException);

		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testWrappedKernelIsHandledWhenRouteIsNotProtected()
	{
		$request = Request::create('/', 'GET');
		$route = new Route('GET', '/', ['protected' => false]);

		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($request)->andReturn($route);

		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testAuthenticationFailsAndThrownExceptionIsHandled()
	{
		$request = Request::create('/', 'GET');
		$route = new Route('GET', '/', ['protected']);
		$auth = m::mock('Dingo\Api\Auth\Shield');

		$auth->shouldReceive('user')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn($auth);

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($request)->andReturn($route);

		$auth->shouldReceive('authenticate')->once()->with($request, $route)->andThrow($exception = new UnauthorizedHttpException(null, 'test'));
		$this->router->shouldReceive('handleException')->once()->with($exception)->andReturn(new Response(['message' => 'test'], 401));
		$this->router->shouldReceive('parseAcceptHeader')->once()->with($request)->andReturn(['v1', 'json']);

		Dingo\Api\Http\Response::setFormatters(['json' => new JsonResponseFormat]);

		$this->assertEquals('{"message":"test"}', $this->middleware->handle($request)->getContent());
	}


	public function testAuthenticationPassesAndWrappedKernelIsHandled()
	{
		$request = Request::create('/', 'GET');
		$route = new Route('GET', '/', ['protected']);
		$auth = m::mock('Dingo\Api\Auth\Shield');

		$auth->shouldReceive('user')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn($auth);
		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($request)->andReturn($route);

		$auth->shouldReceive('authenticate')->once()->with($request, $route);

		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


}
