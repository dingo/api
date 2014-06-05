<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Middleware\RateLimit;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpMiddlewareRateLimitTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
		$this->container = m::mock('Illuminate\Container\Container');
		$this->router = m::mock('Dingo\Api\Routing\Router');
		$this->auth = m::mock('Dingo\Api\Auth\Shield');
		$this->cache = m::mock('Illuminate\Cache\Repository');

		$this->middleware = new RateLimit($this->app, $this->container);

		$this->container->shouldReceive('boot')->atLeast()->once();
		$this->container->shouldReceive('make')->once()->with('dingo.api.auth')->andReturn($this->auth);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testWrappedKernelIsHandledForInternalRequests()
	{
		$request = InternalRequest::create('/', 'GET');

		$this->auth->shouldReceive('check')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => []]));
		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testWrappedKernelIsHandledForRequestsNotTargettingTheApi()
	{
		$request = Request::create('/', 'GET');

		$this->auth->shouldReceive('check')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => []]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);

		$this->router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(false);

		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testWrappedKernelIsHandledForRequestsWhereRateLimitingIsDisabled()
	{
		$request = Request::create('/', 'GET');

		$this->auth->shouldReceive('check')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['unauthenticated' => ['limit' => 0]]]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);

		$this->router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testAuthenticatedConfigurationIsUsedForAuthenticatedRequest()
	{
		$request = Request::create('/', 'GET');

		$this->auth->shouldReceive('check')->once()->andReturn(true);

		$this->container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['authenticated' => ['limit' => 0]]]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);

		$this->router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$this->assertEquals('test', $this->middleware->handle($request)->getContent());
	}


	public function testWrappedKernelIsHandledWhenRateLimitHasNotBeenExceeded()
	{
		$request = Request::create('/', 'GET');

		$this->auth->shouldReceive('check')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['unauthenticated' => ['limit' => 1, 'reset' => 1]]]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->container->shouldReceive('make')->once()->with('cache')->andReturn($this->cache);

		$this->router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$ip = $request->getClientIp();

		$this->cache->shouldReceive('add')->once()->with('dingo:api:requests:'.$ip, 0, 1);
		$this->cache->shouldReceive('add')->once();
		$this->cache->shouldReceive('increment')->once()->with('dingo:api:requests:'.$ip);
		$this->cache->shouldReceive('get')->twice()->with('dingo:api:requests:'.$ip)->andReturn(1);
		$this->cache->shouldReceive('get')->once()->with('dingo:api:reset:'.$ip);

		$this->app->shouldReceive('handle')->once()->with($request, HttpKernelInterface::MASTER_REQUEST, true)->andReturn(new Response('test'));
		$response = $this->middleware->handle($request);
		$this->assertEquals('test', $response->getContent());
		$this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
		$this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));
	}


	public function testForbiddenResponseIsReturnedWhenRateLimitIsExceeded()
	{
		$request = Request::create('/', 'GET');

		$this->auth->shouldReceive('check')->once()->andReturn(false);

		$this->container->shouldReceive('make')->once()->with('config')->andReturn(m::mock(['get' => ['unauthenticated' => ['limit' => 1, 'reset' => 1]]]));
		$this->container->shouldReceive('make')->once()->with('router')->andReturn($this->router);
		$this->container->shouldReceive('make')->once()->with('cache')->andReturn($this->cache);

		$this->router->shouldReceive('parseAcceptHeader')->once()->with($request)->andReturn(['v1', 'json']);
		$this->router->shouldReceive('requestTargettingApi')->once()->with($request)->andReturn(true);

		$ip = $request->getClientIp();

		$this->cache->shouldReceive('add')->once()->with('dingo:api:requests:'.$ip, 0, 1);
		$this->cache->shouldReceive('add')->once();
		$this->cache->shouldReceive('increment')->once()->with('dingo:api:requests:'.$ip);
		$this->cache->shouldReceive('get')->twice()->with('dingo:api:requests:'.$ip)->andReturn(2);
		$this->cache->shouldReceive('get')->once()->with('dingo:api:reset:'.$ip);

		Dingo\Api\Http\Response::setTransformer(m::mock('Dingo\Api\Transformer\Transformer')->shouldReceive('transformableResponse')->andReturn(false)->getMock());
		Dingo\Api\Http\Response::setFormatters(['json' => new Dingo\Api\Http\ResponseFormat\JsonResponseFormat]);

		$response = $this->middleware->handle($request);
		$this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
		$this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));
		$this->assertEquals('{"message":"API rate limit has been exceeded."}', $response->getContent());
		$this->assertEquals(403, $response->getStatusCode());
	}


}
