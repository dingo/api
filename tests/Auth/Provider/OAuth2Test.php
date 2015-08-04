<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Auth\Provider\OAuth2;

class OAuth2Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->server = m::mock('League\OAuth2\Server\ResourceServer');
        $this->provider = new OAuth2($this->server, false);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testExceptionThrownWhenNoScopesProvided()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = m::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $token->shouldReceive('hasScope')->once()->with('foo')->andReturn(false);

        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('scopes')->once()->andReturn(['foo']);

        $this->provider->authenticate($request, $route);
    }

    public function testOnlyOneScopeRequiredToValidateCorrectly()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = m::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $token->shouldReceive('hasScope')->once()->with('foo')->andReturn(true);
        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $session = m::mock('League\OAuth2\Server\Entity\SessionEntity');
        $token->shouldReceive('getSession')->once()->andReturn($session);

        $session->shouldReceive('getOwnerType')->once()->andReturn('client');
        $session->shouldReceive('getOwnerId')->once()->andReturn(1);

        $this->provider->setClientResolver(function ($id) {
            //
        });

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('scopes')->once()->andReturn(['foo', 'bar']);

        $this->assertNull($this->provider->authenticate($request, $route));
    }

    public function testClientIsResolved()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = m::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $session = m::mock('League\OAuth2\Server\Entity\SessionEntity');
        $token->shouldReceive('getSession')->once()->andReturn($session);

        $session->shouldReceive('getOwnerType')->once()->andReturn('client');
        $session->shouldReceive('getOwnerId')->once()->andReturn(1);

        $this->provider->setClientResolver(function ($id) {
            return 'foo';
        });

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('scopes')->once()->andReturn([]);

        $this->assertEquals('foo', $this->provider->authenticate($request, $route));
    }

    public function testUserIsResolved()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = m::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $session = m::mock('League\OAuth2\Server\Entity\SessionEntity');
        $token->shouldReceive('getSession')->once()->andReturn($session);

        $session->shouldReceive('getOwnerType')->once()->andReturn('user');
        $session->shouldReceive('getOwnerId')->once()->andReturn(1);

        $this->provider->setUserResolver(function ($id) {
            return 'foo';
        });

        $route = m::mock('Dingo\Api\Routing\Route');
        $route->shouldReceive('scopes')->once()->andReturn([]);

        $this->assertEquals('foo', $this->provider->authenticate($request, $route));
    }
}
