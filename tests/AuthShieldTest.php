<?php

use Mockery as m;
use Dingo\Api\Auth\Shield;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class AuthShieldTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->auth = m::mock('Illuminate\Auth\AuthManager');
		$this->provider = m::mock('Dingo\Api\Auth\AuthorizationProvider');
	}


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

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new Exception);

		(new Shield($this->auth, [$this->provider]))->authenticate($request, $route);
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('foo'));

		(new Shield($this->auth, [$this->provider]))->authenticate($request, $route);
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->assertEquals(1, (new Shield($this->auth, [$this->provider]))->authenticate($request, $route));
	}


	public function testGettingUserReturnsSetUser()
	{
		$auth = new Shield($this->auth, []);
		$auth->setUser('foo');
		$this->assertEquals('foo', $auth->user());
	}


	public function testGettingUserUsesAuthenticatedUserIdToLogUserIn()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->auth->shouldReceive('check')->once()->andReturn(false);
		$this->auth->shouldReceive('onceUsingId')->once()->with(1)->andReturn(true);
		$this->auth->shouldReceive('user')->once()->andReturn('foo');

		$auth = new Shield($this->auth, [$this->provider]);
		$auth->authenticate($request, $route);
		$this->assertEquals('foo', $auth->user());
	}


	public function testAuthenticatingViaOAuth2GrabsRouteScopesAndAuthenticationSucceeds()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true, 'scopes' => ['foo', 'bar']]);

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$this->assertEquals(1, (new Shield($this->auth, [$this->provider]))->authenticate($request, $route));
	}


}
