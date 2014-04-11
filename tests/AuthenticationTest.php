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
		$provider = $this->getProviderMock();
		$provider->shouldReceive('validateAuthorizationHeader')->once()->andThrow(new Exception);

		$auth = new Authentication($this->router, $this->getAuthMock(), [$provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$this->router->dispatch(Request::create('foo', 'GET'));
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticationFailsWhenProviderFailsToAuthenticateUser()
	{
		$provider = $this->getProviderMock();
		$provider->shouldReceive('validateAuthorizationHeader')->once();
		$provider->shouldReceive('authenticate')->once()->with([])->andThrow(new Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('foo'));

		$auth = new Authentication($this->router, $this->getAuthMock(), [$provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$this->router->dispatch(Request::create('foo', 'GET'));
	}


	public function testAuthenticationSucceedsAndReturnsUserId()
	{
		$provider = $this->getProviderMock();
		$provider->shouldReceive('validateAuthorizationHeader')->once();
		$provider->shouldReceive('authenticate')->once()->with([])->andReturn(1);

		$auth = new Authentication($this->router, $this->getAuthMock(), [$provider]);

		$this->router->filter('api', function() use ($auth) { return $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$response = $this->router->dispatch(Request::create('foo', 'GET'));

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
		$provider = $this->getProviderMock();
		$provider->shouldReceive('validateAuthorizationHeader')->once();
		$provider->shouldReceive('authenticate')->once()->with([])->andReturn(1);

		$auth = new Authentication($this->router, $authManager = $this->getAuthMock(), [$provider]);

		$authManager->shouldReceive('check')->once()->andReturn(false);
		$authManager->shouldReceive('onceUsingId')->once()->with(1)->andReturn(true);
		$authManager->shouldReceive('user')->once()->andReturn('foo');

		$this->router->filter('api', function() use ($auth) { $auth->authenticate(); });
		$this->router->get('foo', ['before' => 'api', 'protected' => true, function(){ return 'foo'; }]);

		$this->router->dispatch(Request::create('foo', 'GET'));

		$this->assertEquals('foo', $auth->user());
	}


	protected function getAuthMock()
	{
		return m::mock('Illuminate\Auth\AuthManager');
	}


	protected function getProviderMock()
	{
		return m::mock('Dingo\Api\Auth\Provider')	;
	}


}