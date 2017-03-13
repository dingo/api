<?php

use Mockery as m;
use Illuminate\Http\Request;
use Dingo\Api\Auth\LaravelAuthProvider;

class AuthLaravelProviderTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->auth = m::mock('Illuminate\Auth\AuthManager');
	}

	public function tearDown()
	{
		m::close();
	}

	public function testIsAuthenticatedAndReturnsUserId()
	{	
		$this->auth->shouldReceive('check')
			->once()
			->andReturn(true);

		$this->auth->shouldReceive('user')
			->once()
			->andReturn((object) ['id' => 1]);


		$request = Request::create('foo', 'GET');
		$provider = new LaravelAuthProvider($this->auth);

		$this->assertEquals(1, $provider->authenticate($request, m::mock('Illuminate\Routing\Route')));
	}

	/**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function testIsNotAuthenticatedThrowsException()
	{
		$request = Request::create('foo', 'GET');
		$provider = new LaravelAuthProvider($this->auth);

		$this->auth->shouldReceive('check')
			->once()
			->andReturn(false);

		$provider->authenticate($request, m::mock('Illuminate\Routing\Route'));
	}

}
