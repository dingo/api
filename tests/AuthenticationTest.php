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

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => false]));

		$auth = new Authentication($router, $this->getAuthMock(), []);

		$this->assertNull($auth->authenticate());
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenNoAuthorizationHeaderIsSet()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andThrow(new Exception);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => true]));

		$auth = new Authentication($router, $this->getAuthMock(), [$provider]);
		$auth->authenticate();
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andThrow(new Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('foo'));

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => true]));

		$auth = new Authentication($router, $this->getAuthMock(), [$provider]);

		$auth->authenticate();
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andReturn(1);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => true]));

		$auth = new Authentication($router, $this->getAuthMock(), ['basic' => $provider]);

		$this->assertEquals(1, $auth->authenticate());
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

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andReturn(1);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => true]));

		$authManager = $this->getAuthMock();
		$authManager->shouldReceive('check')->once()->andReturn(false);
		$authManager->shouldReceive('onceUsingId')->once()->with(1)->andReturn(true);
		$authManager->shouldReceive('user')->once()->andReturn('foo');

		$auth = new Authentication($router, $authManager, ['basic' => $provider]);

		$auth->authenticate();

		$this->assertEquals('foo', $auth->user());
	}


	public function testAuthenticatingViaOAuth2GrabsRouteScopesAndAuthenticationSucceeds()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('setScopes')->once()->with(['foo', 'bar']);
		$provider->shouldReceive('authenticate')->once()->with($request)->andReturn(1);

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => true, 'scopes' => ['foo', 'bar']]));

		$auth = new Authentication($router, $this->getAuthMock(), ['oauth2' => $provider]);

		$this->assertEquals(1, $auth->authenticate());
	}


	public function testAuthenticatingWithCustomProvider()
	{
		$request = Request::create('foo', 'GET');

		$router = $this->getRouterMock();
		$router->shouldReceive('getCurrentRequest')->once()->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->once()->andReturn(new Route('GET', 'foo', ['protected' => true]));

		$auth = new Authentication($router, $this->getAuthMock(), []);
		$auth->extend('foo', new CustomProviderStub);

		$this->assertEquals(1, $auth->authenticate());
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


class CustomProviderStub implements Dingo\Api\Auth\ProviderInterface {

	public function authenticate(Illuminate\Http\Request $request)
	{
		return 1;
	}

}