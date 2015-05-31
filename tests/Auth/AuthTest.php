<?php

namespace Dingo\Api\Tests\Auth;

use Mockery as m;
use Dingo\Api\Auth\Auth;
use Dingo\Api\Http\Request;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->router = m::mock('Dingo\Api\Routing\Router');
        $this->auth = new Auth($this->router, $this->container, []);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderNotSet()
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock('Dingo\Api\Routing\Route'));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock('Dingo\Api\Auth\Provider\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new BadRequestHttpException);

        $this->auth->extend('provider', $provider);

        $this->auth->authenticate();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testExceptionThrownWhenProviderFailsToAuthenticate()
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock('Dingo\Api\Routing\Route'));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock('Dingo\Api\Auth\Provider\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new UnauthorizedHttpException('foo'));

        $this->auth->extend('provider', $provider);

        $this->auth->authenticate();
    }

    public function testAuthenticationIsSuccessfulAndUserIsSet()
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock('Dingo\Api\Routing\Route'));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock('Dingo\Api\Auth\Provider\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn((object) ['id' => 1]);

        $this->auth->extend('provider', $provider);

        $user = $this->auth->authenticate();

        $this->assertEquals(1, $user->id);
    }

    public function testProvidersAreFilteredWhenSpecificProviderIsRequested()
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock('Dingo\Api\Routing\Route'));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock('Dingo\Api\Auth\Provider\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn((object) ['id' => 1]);

        $this->auth->extend('one', m::mock('Dingo\Api\Auth\Provider\Provider'));
        $this->auth->extend('two', $provider);

        $this->auth->authenticate(['two']);
        $this->assertEquals($provider, $this->auth->getProviderUsed());
    }

    public function testGettingUserWhenNotAuthenticatedAttemptsToAuthenticateAndReturnsNull()
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn('foo');
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn('bar');

        $this->auth->extend('provider', m::mock('Dingo\Api\Auth\Provider\Provider'));

        $this->assertNull($this->auth->user());
    }

    public function testGettingUserWhenAlreadyAuthenticatedReturnsUser()
    {
        $this->auth->setUser((object) ['id' => 1]);

        $this->assertEquals(1, $this->auth->user()->id);
        $this->assertTrue($this->auth->check());
    }
}
