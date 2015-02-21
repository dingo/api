<?php

namespace Dingo\Api\Tests\Auth;

use Mockery;
use Dingo\Api\Properties;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Auth\Authenticator;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticatorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container;
        $this->router = new Router(Mockery::mock('Illuminate\Events\Dispatcher'), new Properties, $this->container);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderNotSet()
    {
        $this->router->setCurrentRoute($route = new Route(['GET'], 'foo', ['protected' => true]));
        $this->router->setCurrentRequest($request = Request::create('foo', 'GET'));

        $provider = Mockery::mock('Dingo\Api\Auth\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new BadRequestHttpException);

        $auth = new Authenticator($this->router, $this->container, ['provider' => $provider]);

        $auth->authenticate();
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testExceptionThrownWhenProviderFailsToAuthenticate()
    {
        $this->router->setCurrentRoute($route = new Route(['GET'], 'foo', ['protected' => true]));
        $this->router->setCurrentRequest($request = Request::create('foo', 'GET'));

        $provider = Mockery::mock('Dingo\Api\Auth\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new UnauthorizedHttpException('foo'));

        $auth = new Authenticator($this->router, $this->container, ['provider' => $provider]);

        $auth->authenticate();
    }

    public function testAuthenticationIsSuccessfulAndUserIsSet()
    {
        $this->router->setCurrentRoute($route = new Route(['GET'], 'foo', ['protected' => true]));
        $this->router->setCurrentRequest($request = Request::create('foo', 'GET'));

        $provider = Mockery::mock('Dingo\Api\Auth\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn((object) ['id' => 1]);

        $auth = new Authenticator($this->router, $this->container, ['provider' => $provider]);

        $user = $auth->authenticate();

        $this->assertEquals(1, $user->id);
    }

    public function testProvidersAreFilteredWhenSpecificProviderIsRequested()
    {
        $this->router->setCurrentRoute($route = new Route(['GET'], 'foo', ['protected' => true]));
        $this->router->setCurrentRequest($request = Request::create('foo', 'GET'));

        $provider = Mockery::mock('Dingo\Api\Auth\Provider');
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(true);
        $provider->shouldReceive('assert')->once()->andReturn('one');

        $auth = new Authenticator($this->router, $this->container, [
            'one' => $provider,
            'two' => Mockery::mock('Dingo\Api\Auth\Provider')
        ]);

        $auth->authenticate(['one']);
        $this->assertEquals('one', $auth->getProviderUsed()->assert());
    }

    public function testGettingUserWhenNotAuthenticatedAttemptsToAuthenticateAndReturnsNull()
    {
        $this->router->setCurrentRoute($route = new Route(['GET'], 'foo', ['protected' => true]));
        $this->router->setCurrentRequest($request = Request::create('foo', 'GET'));

        $auth = new Authenticator($this->router, $this->container, ['provider' => Mockery::mock('Dingo\Api\Auth\Provider')]);

        $this->assertNull($auth->user());
    }

    public function testGettingUserWhenAlreadyAuthenticatedReturnsUser()
    {
        $auth = new Authenticator($this->router, $this->container, ['provider' => Mockery::mock('Dingo\Api\Auth\Provider')]);

        $auth->setUser((object) ['id' => 1]);

        $this->assertEquals(1, $auth->user()->id);
        $this->assertTrue($auth->check());
    }
}
