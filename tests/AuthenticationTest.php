<?php

use Mockery as m;
use Illuminate\Http\Request;
use Dingo\Api\Authentication;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\InternalRequest;

class AuthenticationTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->router = new Router(new Illuminate\Events\Dispatcher);
	}


	public function testAuthenticationNotRequiredForInternalRequest()
	{
		$auth = new Authentication($this->router, $this->getAuthMock(), []);

		$this->router->get('foo', ['protected' => true, function(){ return 'foo'; }]);
		$this->router->before(function() use ($auth)
		{
			return $auth->authenticate();
		});

		$response = $this->router->dispatch(InternalRequest::create('foo', 'GET'));

		$this->assertEquals('foo', $response->getContent());
	}


	public function testAuthenticationNotRequiredWhenAuthenticatedUserExists()
	{
		$auth = new Authentication($this->router, $this->getAuthMock(), []);
		$auth->setUser('foo');

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$response = $this->router->dispatch(Request::create('foo', 'GET'));

		$this->assertEquals('foo', $response->getContent());
	}


	public function testAuthenticationNotRequiredWhenRouteIsNotProtected()
	{
		$auth = new Authentication($this->router, $this->getAuthMock(), []);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => false, function(){ return 'foo'; }]);

		$response = $this->router->dispatch(Request::create('foo', 'GET'));

		$this->assertEquals('foo', $response->getContent());
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 * @expectedExceptionMessage Failed to authenticate because of an invalid or missing authorization header.
	 */
	public function testAuthenticationFailsWhenNoAuthorizationHeaderIsSet()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andThrow(new Exception);

		$auth = new Authentication($this->router, $this->getAuthMock(), [$provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$this->router->dispatch($request);
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andThrow(new Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('foo'));

		$auth = new Authentication($this->router, $this->getAuthMock(), [$provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$this->router->dispatch($request);
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andReturn(1);

		$auth = new Authentication($this->router, $this->getAuthMock(), ['basic' => $provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$response = $this->router->dispatch($request);

		$this->assertEquals(1, $response->getContent());
	}


	public function testGettingUserReturnsSetUser()
	{
		$auth = new Authentication($this->router, $this->getAuthMock(), []);

		$auth->setUser('foo');

		$this->assertEquals('foo', $auth->user());
	}


	public function testGettingUserUsesAuthenticatedUserIdToLogUserIn()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('authenticate')->once()->with($request)->andReturn(1);

		$auth = new Authentication($this->router, $authManager = $this->getAuthMock(), ['basic' => $provider]);

		$authManager->shouldReceive('check')->once()->andReturn(false);
		$authManager->shouldReceive('onceUsingId')->once()->with(1)->andReturn(true);
		$authManager->shouldReceive('user')->once()->andReturn('foo');

		$this->router->filter('api', function() use ($auth) { $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$this->router->dispatch($request);

		$this->assertEquals('foo', $auth->user());
	}


	public function testAuthenticatingViaOAuth2GrabsRouteScopesAndAuthenticationSucceeds()
	{
		$request = Request::create('foo', 'GET');

		$provider = $this->getProviderMock();
		$provider->shouldReceive('setScopes')->once()->with(['foo', 'bar']);
		$provider->shouldReceive('authenticate')->once()->with($request)->andReturn(1);

		$auth = new Authentication($this->router, $this->getAuthMock(), ['oauth2' => $provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, 'scopes' => ['foo', 'bar'], function(){ return 'foo'; }]);

		$response = $this->router->dispatch($request);

		$this->assertEquals(1, $response->getContent());
	}


	public function testAuthenticatingWithCustomProvider()
	{
		$request = Request::create('foo', 'GET');

		$auth = new Authentication($this->router, $this->getAuthMock(), []);
		$auth->extend('foo', new CustomProviderStub);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$response = $this->router->dispatch($request);

		$this->assertEquals(1, $response->getContent());
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


class CustomProviderStub implements Dingo\Api\Auth\ProviderInterface {

	public function authenticate(Illuminate\Http\Request $request)
	{
		return 1;
	}

}