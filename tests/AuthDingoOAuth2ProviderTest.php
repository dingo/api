<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Dingo\Api\Auth\DingoOAuth2Provider;
use Dingo\OAuth2\Exception\InvalidTokenException;

class AuthDingoOAuth2ProviderTest extends PHPUnit_Framework_TestCase {


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
		$provider = new DingoOAuth2Provider($this->getResourceMock());
		$provider->authenticate($request, new Route('/foo', 'GET', []));
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticatingFailsAndThrowsException()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new DingoOAuth2Provider($resource = $this->getResourceMock());
		$resource->shouldReceive('validateRequest')->once()->with([])->andThrow(new InvalidTokenException('foo', 'foo', 403));

		$provider->authenticate($request, new Route('/foo', 'GET', []));
	}


	public function testAuthenticatingSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new DingoOAuth2Provider($resource = $this->getResourceMock());
		$resource->shouldReceive('validateRequest')->once()->with(['foo', 'bar'])->andReturn(m::mock(['getUserId' => 1]));

		$this->assertEquals(1, $provider->authenticate($request, new Route('/foo', 'GET', ['scopes' => ['foo', 'bar']])));
	}


	public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 'foo']);

		$provider = new DingoOAuth2Provider($resource = $this->getResourceMock());
		$resource->shouldReceive('validateRequest')->once()->with(['foo', 'bar'])->andReturn(m::mock(['getUserId' => 1]));

		$this->assertEquals(1, $provider->authenticate($request, new Route('/foo', 'GET', ['scopes' => ['foo', 'bar']])));
	}


	protected function getResourceMock()
	{
		return m::mock('Dingo\OAuth2\Server\Resource');
	}


}
