<?php

use Mockery as m;
use Illuminate\Http\Request;
use Dingo\Api\Auth\OAuth2Provider;
use Dingo\OAuth2\Exception\InvalidTokenException;

class AuthOAuth2ProviderTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	/**
	 * @expectedException \Exception
	 */
	public function testValidatingAuthorizationHeaderFailsAndThrowsException()
	{
		$request = Request::create('foo', 'GET');

		$provider = new OAuth2Provider($this->getResourceMock(), []);

		$provider->authenticate($request);
	}


	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testAuthenticatingFailsAndThrowsException()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new OAuth2Provider($resource = $this->getResourceMock(), []);
		$resource->shouldReceive('validateRequest')->once()->with([])->andThrow(new InvalidTokenException('foo', 'foo', 403));

		$provider->authenticate($request);
	}


	public function testAuthenticatingSucceedsAndReturnsUserId()
	{
		$request = Request::create('foo', 'GET');
		$request->headers->set('authorization', 'Bearer foo');

		$provider = new OAuth2Provider($resource = $this->getResourceMock(), []);
		$resource->shouldReceive('validateRequest')->once()->with(['foo', 'bar'])->andReturn(m::mock([
			'getUserId' => 1
		]));

		$provider->setScopes(['foo', 'bar']);

		$this->assertEquals(1, $provider->authenticate($request));
	}


	protected function getResourceMock()
	{
		return m::mock('Dingo\OAuth2\Server\Resource');
	}


}