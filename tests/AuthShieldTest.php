<?php

use Mockery as m;
use Dingo\Api\Auth\Shield;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthShieldTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->auth = m::mock('Illuminate\Auth\AuthManager');
		$this->container = m::mock('Illuminate\Container\Container');
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

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new BadRequestHttpException);

		$auth = new Shield($this->auth, $this->container, ['provider' => $this->provider]);
		$auth->setRequest($request);
		$auth->setRoute($route);

		$auth->authenticate();
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new UnauthorizedHttpException('foo'));

		$auth = new Shield($this->auth, $this->container, ['provider' => $this->provider]);
		$auth->setRequest($request);
		$auth->setRoute($route);

		$auth->authenticate();
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', ['protected' => true]);

		$this->provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(1);

		$auth = new Shield($this->auth, $this->container, ['provider' => $this->provider]);
		$auth->setRequest($request);
		$auth->setRoute($route);

		$this->assertEquals(1, $auth->authenticate());
	}


	public function testGettingUserReturnsSetUser()
	{
		$auth = new Shield($this->auth, $this->container, []);
		$auth->setUser('foo');
		$this->assertEquals('foo', $auth->user());
		$this->assertTrue($auth->check());
	}


	public function testRegisteringCustomProviderAtRuntime()
	{
		$request = Request::create('foo', 'GET');
		$route = new Route('GET', 'foo', []);
		$auth = new Shield($this->auth, $this->container, []);
		$auth->setRequest($request);
		$auth->setRoute($route);

		$auth->extend('custom', new CustomProviderStub);
		$auth->authenticate($request, $route);
		$this->assertInstanceOf('CustomProviderStub', $auth->getProviderUsed());

		$auth->extend('custom', function($app)
		{
			$this->assertInstanceOf('Illuminate\Container\Container', $app);

			return new CustomProviderStub;
		});
		
		$auth->authenticate();
		$this->assertInstanceOf('CustomProviderStub', $auth->getProviderUsed());	
	}


	public function testGettingUserWhenNoLoggedInUserReturnsNull()
	{
		$auth = new Shield($this->auth, $this->container, []);
		$this->assertFalse($auth->check());
		$this->assertNull($auth->user());
	}


}
