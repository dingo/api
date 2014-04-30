<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Response as ApiResponse;
use Dingo\Api\Http\Middleware\Authentication;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class HttpMiddlewareAuthenticationTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->response = new Response;
		$this->app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$this->container = m::mock('Illuminate\Container\Container');
		$this->request = Request::create('/', 'GET');
		$this->router = m::mock('Dingo\Api\Routing\Router');
		$this->collection = m::mock('Dingo\Api\Routing\ApiRouteCollection');

		$this->container->shouldReceive('boot')->atLeast()->once();

		$this->middleware = new Authentication($this->app, $this->container);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testWrappedKernelIsHandledForInternalRequestsAndAuthenticatedUsers()
	{
		$this->request = InternalRequest::create('/', 'GET');

		$this->app->shouldReceive('handle')->once()->with($this->request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($this->response);
		$this->assertEquals($this->response, $this->middleware->handle($this->request));

		$this->request = Request::create('/', 'GET');

		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => true]));
		$this->app->shouldReceive('handle')->once()->with($this->request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($this->response);
		$this->assertEquals($this->response, $this->middleware->handle($this->request));
	}


	public function testWrappedKernelIsHandledWhenNoApiRouteCollectionForRequest()
	{
		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->app->shouldReceive('handle')->once()->with($this->request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($this->response);

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($this->request)->andReturn(null);

		$this->assertEquals($this->response, $this->middleware->handle($this->request));
	}


	public function testWrappedKernelIsHandledWhenRouteNotFoundInApiRouteCollection()
	{
		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->app->shouldReceive('handle')->once()->with($this->request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($this->response);

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($this->request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($this->request)->andThrow(new NotFoundHttpException);

		$this->assertEquals($this->response, $this->middleware->handle($this->request));
	}


	public function testWrappedKernelIsHandledWhenRouteIsNotProtected()
	{
		$route = new Route('GET', '/', ['protected' => false]);

		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->app->shouldReceive('handle')->once()->with($this->request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($this->response);

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($this->request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($this->request)->andReturn($route);

		$this->assertEquals($this->response, $this->middleware->handle($this->request));
	}


	public function testAuthenticationFailsAndThrownExceptionIsHandled()
	{
		$route = new Route('GET', '/', ['protected']);
		$auth = m::mock('Dingo\Api\Auth\Shield');

		$auth->shouldReceive('user')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn($auth);

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($this->request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($this->request)->andReturn($route);

		$auth->shouldReceive('authenticate')->once()->with($this->request, $route)->andThrow($exception = new UnauthorizedHttpException(null, 'test'));
		$this->router->shouldReceive('handleException')->once()->with($exception)->andReturn(new Response('test', 401));
		$this->router->shouldReceive('getRequestedFormat')->once()->andReturn('json');

		ApiResponse::setFormatters(['json' => new JsonResponseFormat]);

		$this->assertEquals('{"message":"test"}', $this->middleware->handle($this->request)->getContent());
	}


	public function testAuthenticationPassesAndWrappedKernelIsHandled()
	{
		$route = new Route('GET', '/', ['protected']);
		$auth = m::mock('Dingo\Api\Auth\Shield');

		$auth->shouldReceive('user')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn($auth);
		$this->app->shouldReceive('handle')->once()->with($this->request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($this->response);

		$this->router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($this->request)->andReturn($this->collection);

		$this->collection->shouldReceive('match')->once()->with($this->request)->andReturn($route);

		$auth->shouldReceive('authenticate')->once()->with($this->request, $route);

		$this->assertEquals($this->response, $this->middleware->handle($this->request));
	}


}
