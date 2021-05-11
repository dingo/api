<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Dingo\Api\Auth\Provider\SanctumSPA;
use Dingo\Api\Routing\Route;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SanctumSPATest extends BaseTestCase
{
    protected $auth;
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock('Illuminate\Auth\AuthManager');
        $this->provider = new SanctumSPA($this->auth);
    }

    public function testInvalidSanctumCredentialsThrowsException()
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('GET', '/');

        $this->auth->shouldReceive('guard')->andReturn(m::self());

        $this->auth->shouldReceive('user')->once()->andReturn(null);

        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('GET', '/');

        $this->auth->shouldReceive('guard')->andReturn(m::self());

        $this->auth->shouldReceive('user')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock(Route::class))->id);
    }
}
