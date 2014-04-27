<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Middleware\RateLimit;
use Dingo\Api\Http\Response as ApiResponse;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpMiddlewareRateLimitTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testWrappedKernelIsHandledForInternalRequests()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$middleware = new RateLimit($app, $container);
		$request = InternalRequest::create('/', 'GET');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => []]));
		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testWrappedKernelIsHandledForRequestsNotTargettingTheApi()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$middleware = new RateLimit($app, $container);
		$request = Request::create('/', 'GET');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => []]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);

		$router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(false);

		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testWrappedKernelIsHandledForRequestsWhereRateLimitingIsDisabled()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$middleware = new RateLimit($app, $container);
		$request = Request::create('/', 'GET');
		$shield = m::mock('Dingo\Api\Auth\Shield');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['unauthenticated' => ['limit' => 0]]]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn($shield);

		$router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$shield->shouldReceive('check')->once()->andReturn(false);

		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$this->assertEquals($response, $middleware->handle($request));
	}


	public function testWrappedKernelIsHandledWhenRateLimitHasNotBeenExceeded()
	{
		$response = new Response;
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$middleware = new RateLimit($app, $container);
		$request = Request::create('/', 'GET');
		$shield = m::mock('Dingo\Api\Auth\Shield');
		$cache = m::mock('Illuminate\Cache\Repository');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['unauthenticated' => ['limit' => 1, 'reset' => 1]]]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn($shield);
		$container->shouldReceive('make')->once()->with('cache')->andReturn($cache);

		$router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$shield->shouldReceive('check')->times(5)->andReturn(false);

		$ip = $request->getClientIp();

		$cache->shouldReceive('add')->once()->with('dingo:api:limit:'.$ip, 0, 1);
		$cache->shouldReceive('add')->once()->with('dingo:api:reset:'.$ip, time() + 60, 1);
		$cache->shouldReceive('increment')->once()->with('dingo:api:limit:'.$ip);
		$cache->shouldReceive('get')->once()->with('dingo:api:limit:'.$ip)->andReturn(1);
		$cache->shouldReceive('get')->once()->with('dingo:api:reset:'.$ip)->andReturn(time() + 60);

		$app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$response = $middleware->handle($request);
		$this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
		$this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));
	}


	public function testForbiddenResponseIsReturnedWhenRateLimitIsExceeded()
	{
		$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$container = m::mock('Illuminate\Container\Container');
		$router = m::mock('Dingo\Api\Routing\Router');
		$middleware = new RateLimit($app, $container);
		$request = Request::create('/', 'GET');
		$shield = m::mock('Dingo\Api\Auth\Shield');
		$cache = m::mock('Illuminate\Cache\Repository');

		$container->shouldReceive('boot')->once();
		$container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['unauthenticated' => ['limit' => 1, 'reset' => 1]]]));
		$container->shouldReceive('make')->once()->with('router')->andReturn($router);
		$container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn($shield);
		$container->shouldReceive('make')->once()->with('cache')->andReturn($cache);

		$router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$shield->shouldReceive('check')->times(5)->andReturn(false);

		$ip = $request->getClientIp();

		$cache->shouldReceive('add')->once()->with('dingo:api:limit:'.$ip, 0, 1);
		$cache->shouldReceive('add')->once()->with('dingo:api:reset:'.$ip, time() + 60, 1);
		$cache->shouldReceive('increment')->once()->with('dingo:api:limit:'.$ip);
		$cache->shouldReceive('get')->once()->with('dingo:api:limit:'.$ip)->andReturn(2);
		$cache->shouldReceive('get')->once()->with('dingo:api:reset:'.$ip)->andReturn(time() + 60);

		$response = $middleware->handle($request);
		$this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
		$this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));
		$this->assertEquals('{"message":"API rate limit has been exceeded."}', $response->getContent());
		$this->assertEquals(403, $response->getStatusCode());
	}


}
