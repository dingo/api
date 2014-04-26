<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Dingo\Api\Authentication;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\InternalRequest;

class AuthenticationTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testAuthenticationNotRequiredForInternalRequest()
	{
		$request = InternalRequest::create('foo', 'GET');

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);

		$auth = new Authentication($router, $this->getAuthMock(), []);

		$this->assertNull($auth->authenticate());
	}


	public function testAuthenticationNotRequiredWhenAuthenticatedUserExists()
	{
		$request = Request::create('foo', 'GET');

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);

		$auth = new Authentication($router, $this->getAuthMock(), []);
		$auth->setUser('foo');

		$this->assertNull($auth->authenticate());
	}


	public function testAuthenticationNotRequiredWhenRouteIsNotProtected()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => false]);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

		$this->assertNull((new Authentication($router, $this->getAuthMock(), []))->authenticate());
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenNoAuthorizationHeaderIsSet()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new Exception);

		(new Authentication($router, $this->getAuthMock(), [$provider]))->authenticate();
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('foo'));

		(new Authentication($router, $this->getAuthMock(), [$provider]))->authenticate();
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->assertEquals(1, (new Authentication($router, $this->getAuthMock(), ['basic' => $provider]))->authenticate());
	}


	public function testGettingUserReturnsSetUser()
	{
		$auth = new Authentication($this->getRouterMock(), $this->getAuthMock(), []);
		$auth->setUser('foo');
		$this->assertEquals('foo', $auth->user());
	}


	public function testGettingUserUsesAuthenticatedUserIdToLogUserIn()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$manager = $this->getAuthMock();
		$manager->shouldReceive('check')->once()->andReturn(false);
		$manager->shouldReceive('onceUsingId')->once()->with(1)->andReturn(true);
		$manager->shouldReceive('user')->once()->andReturn('foo');

		$auth = new Authentication($router, $manager, ['basic' => $provider]);
		$auth->authenticate();
		$this->assertEquals('foo', $auth->user());
	}


	public function testAuthenticatingViaOAuth2GrabsRouteScopesAndAuthenticationSucceeds()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true, 'scopes' => ['foo', 'bar']]);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->assertEquals(1, (new Authentication($router, $this->getAuthMock(), ['oauth2' => $provider]))->authenticate());
	}


	protected function getAuthMock()
	{
		return m::mock('Illuminate\Auth\AuthManager');
	}


	protected function getProviderMock()
	{
		return m::mock('Dingo\Api\Auth\AuthorizationProvider');
	}


	protected function getRouterMock()
	{
		return m::mock('Dingo\Api\Routing\Router');
	}


}
