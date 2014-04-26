<?php

use Mockery as m;
use Dingo\Api\Auth\Shield;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class AuthShieldTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenNoAuthorizationHeaderIsSet()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new Exception);

		(new Shield($this->getAuthMock(), [$provider]))->authenticate($request, $route);
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('foo'));

		(new Shield($this->getAuthMock(), [$provider]))->authenticate($request, $route);
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->assertEquals(1, (new Shield($this->getAuthMock(), ['basic' => $provider]))->authenticate($request, $route));
	}


	public function testGettingUserReturnsSetUser()
	{
		$auth = new Shield($this->getAuthMock(), []);
		$auth->setUser('foo');
		$this->assertEquals('foo', $auth->user());
	}


	public function testGettingUserUsesAuthenticatedUserIdToLogUserIn()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$manager = $this->getAuthMock();
		$manager->shouldReceive('check')->once()->andReturn(false);
		$manager->shouldReceive('onceUsingId')->once()->with(1)->andReturn(true);
		$manager->shouldReceive('user')->once()->andReturn('foo');

		$auth = new Shield($manager, ['basic' => $provider]);
		$auth->authenticate($request, $route);
		$this->assertEquals('foo', $auth->user());
	}


	public function testAuthenticatingViaOAuth2GrabsRouteScopesAndAuthenticationSucceeds()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true, 'scopes' => ['foo', 'bar']]);

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->assertEquals(1, (new Shield($this->getAuthMock(), ['oauth2' => $provider]))->authenticate($request, $route));
	}


	protected function getAuthMock()
	{
		return m::mock('Illuminate\Auth\AuthManager');
	}


	protected function getProviderMock()
	{
		return m::mock('Dingo\Api\Auth\AuthorizationProvider');
	}


}
