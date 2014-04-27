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
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class HttpMiddlewareAuthenticationTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testLaravelMiddlewareIsHandledForInternalRequestsAndAuthenticatedUsers()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$middleware = new Authentication($app, $container);
		$request = InternalRequest::create('/', 'GET');

		$container->shouldReceive('boot')->once();
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$this->assertEquals($response, $middleware->handle($request));

		$request = Request::create('/', 'GET');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => true]));
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);

		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testLaravelMiddlewareIsHandledWhenNoApiRouteCollectionForRequest()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$middleware = new Authentication($app, $container);
		$request = Request::create('/', 'GET');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);

		$router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn(null);

		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testLaravelMiddlewareIsHandledWhenRouteNotFoundInApiRouteCollection()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$collection = m::mock('Dingo\Api\Routing\ApiRouteCollection');
		$middleware = new Authentication($app, $container);
		$request = Request::create('/', 'GET');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);

		$router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($collection);

		$collection->shouldReceive('match')->once()->with($request)->andReturn(null);

		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testLaravelMiddlewareIsHandledWhenRouteIsNotProtected()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$collection = m::mock('Dingo\Api\Routing\ApiRouteCollection');
		$middleware = new Authentication($app, $container);
		$request = Request::create('/', 'GET');
		$route = new Route('GET', '/', ['protected' => false]);

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);

		$router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($collection);

		$collection->shouldReceive('match')->once()->with($request)->andReturn($route);

		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testAuthenticationFailsAndThrowExceptionIsHandled()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$collection = m::mock('Dingo\Api\Routing\ApiRouteCollection');
		$middleware = new Authentication($app, $container);
		$request = Request::create('/', 'GET');
		$route = new Route('GET', '/', ['protected']);
		$shield = m::mock('Dingo\Api\Auth\Shield');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$container->shouldReceive('make')->twice()->with('router')->andReturn($router);
		$container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn($shield);

		$router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($collection);

		$collection->shouldReceive('match')->once()->with($request)->andReturn($route);

		$shield->shouldReceive('authenticate')->once()->with($request, $route)->andThrow($exception = new UnauthorizedHttpException(null, 'test'));
		$router->shouldReceive('handleException')->once()->with($exception)->andReturn(new Response('test', 401));
		$router->shouldReceive('getRequestedFormat')->once()->andReturn('json');

		ApiResponse::setFormatters(['json' => new JsonResponseFormat]);

		$this->assertEquals('{"message":"test"}', $middleware->handle($request)->getContent());
	}


	public function testAuthenticationPassesAndLaravelMiddlewareIsHandled()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$collection = m::mock('Dingo\Api\Routing\ApiRouteCollection');
		$middleware = new Authentication($app, $container);
		$request = Request::create('/', 'GET');
		$route = new Route('GET', '/', ['protected']);
		$shield = m::mock('Dingo\Api\Auth\Shield');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn(m::mock(['user' => false]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$container->shouldReceive('make')->once()->once('dingo.api.auth')->andReturn($shield);
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);

		$router->shouldReceive('getApiRouteCollectionFromRequest')->once()->with($request)->andReturn($collection);

		$collection->shouldReceive('match')->once()->with($request)->andReturn($route);

		$shield->shouldReceive('authenticate')->once()->with($request, $route);

		$this->assertEquals($response, $middleware->handle($request));
	}


}
