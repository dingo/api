<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Dingo\Api\Auth\LeagueOAuth2Provider;
use League\OAuth2\Server\Exception\InvalidAccessTokenException;

class AuthLeagueOAuth2ProviderTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
	 */
	public function testValidatingAuthorizationHeaderFailsAndThrowsException()
	{
		$request = Request::create('foo', 'GET');
		$provider = new LeagueOAuth2Provider($this->getResourceMock());
		$provider->authenticate($request, new Route('/foo', 'GET', []));
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticatingFailsAndThrowsException()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new LeagueOAuth2Provider($resource = $this->getResourceMock());
		$resource->shouldReceive('isValid')->once()->with(false)->andThrow(new InvalidAccessTokenException('foo'));

		$provider->authenticate($request, new Route('/foo', 'GET', []));
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticatingSucceedsButScopesDoNotMatchAndThrowsException()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new LeagueOAuth2Provider($resource = $this->getResourceMock());
		$resource->shouldReceive('isValid')->once()->with(false);
		$resource->shouldReceive('hasScope')->once()->with('foo')->andReturn(false);

		$provider->authenticate($request, new Route('/foo', 'GET', ['scopes' => 'foo']));
	}


	public function testAuthenticatingSucceedsAndReturnsUserObject()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new LeagueOAuth2Provider($resource = $this->getResourceMock());
		$provider->setUserCallback(function($id)
		{
			return (object) ['id' => 1];
		});
		$resource->shouldReceive('isValid')->once()->with(false);
		$resource->shouldReceive('hasScope')->once()->with('foo')->andReturn(true);
		$resource->shouldReceive('hasScope')->once()->with('bar')->andReturn(true);
		$resource->shouldReceive('getOwnerId')->once()->andReturn(1);
		$resource->shouldReceive('getOwnerType')->once()->andReturn('user');

		$this->assertEquals(1, $provider->authenticate($request, new Route('/foo', 'GET', ['scopes' => ['foo', 'bar']]))->id);
	}


	public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 'foo']);

		$provider = new LeagueOAuth2Provider($resource = $this->getResourceMock());
		$provider->setClientCallback(function($id)
		{
			return (object) ['id' => 1];
		});
		$resource->shouldReceive('isValid')->once()->with(false);
		$resource->shouldReceive('hasScope')->once()->with('foo')->andReturn(true);
		$resource->shouldReceive('hasScope')->once()->with('bar')->andReturn(true);
		$resource->shouldReceive('getOwnerId')->once()->andReturn(1);
		$resource->shouldReceive('getOwnerType')->once()->andReturn('client');

		$this->assertEquals(1, $provider->authenticate($request, new Route('/foo', 'GET', ['scopes' => ['foo', 'bar']]))->id);
	}


	protected function getResourceMock()
	{
		return m::mock('League\OAuth2\Server\Resource');
	}

}
