<?php

namespace Dingo\Api\Tests\Auth;

use Mockery;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Auth\LeagueOAuth2Provider;

class LeagueOAuth2ProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->server = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $this->provider = new LeagueOAuth2Provider($this->server, false);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testExceptionThrownWhenNoScopesProvided()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = Mockery::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $token->shouldReceive('hasScope')->once()->with('foo')->andReturn(false);

        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $this->provider->authenticate($request, new Route('GET', '/', ['scopes' => 'foo']));
    }

    public function testOnlyOneScopeRequiredToValidateCorrectly()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = Mockery::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $token->shouldReceive('hasScope')->once()->with('foo')->andReturn(true);
        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $session = Mockery::mock('League\OAuth2\Server\Entity\SessionEntity');
        $token->shouldReceive('getSession')->once()->andReturn($session);

        $session->shouldReceive('getOwnerType')->once()->andReturn('client');
        $session->shouldReceive('getOwnerId')->once()->andReturn(1);

        $this->provider->setClientResolver(function ($id) {});

        $this->assertNull($this->provider->authenticate($request, new Route('GET', '/', ['scopes' => 'foo|bar'])));
    }

    public function testClientIsResolved()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = Mockery::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $session = Mockery::mock('League\OAuth2\Server\Entity\SessionEntity');
        $token->shouldReceive('getSession')->once()->andReturn($session);

        $session->shouldReceive('getOwnerType')->once()->andReturn('client');
        $session->shouldReceive('getOwnerId')->once()->andReturn(1);

        $this->provider->setClientResolver(function ($id) {
            return 'foo';
        });

        $this->assertEquals('foo', $this->provider->authenticate($request, new Route('GET', '/', [])));
    }

    public function testUserIsResolved()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->server->shouldReceive('isValidRequest')->once()->andReturn(true);

        $token = Mockery::mock('League\OAuth2\Server\Entity\AccessTokenEntity');
        $this->server->shouldReceive('getAccessToken')->once()->andReturn($token);

        $session = Mockery::mock('League\OAuth2\Server\Entity\SessionEntity');
        $token->shouldReceive('getSession')->once()->andReturn($session);

        $session->shouldReceive('getOwnerType')->once()->andReturn('user');
        $session->shouldReceive('getOwnerId')->once()->andReturn(1);

        $this->provider->setUserResolver(function ($id) {
            return 'foo';
        });

        $this->assertEquals('foo', $this->provider->authenticate($request, new Route('GET', '/', [])));
    }
}
