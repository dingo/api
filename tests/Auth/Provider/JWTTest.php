<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Auth\Provider\JWT;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTTest extends TestCase
{
    protected $auth;
    protected $provider;

    public function setUp()
    {
        $this->auth = m::mock('Tymon\JWTAuth\JWTAuth');
        $this->provider = new JWT($this->auth);
    }

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
        $this->provider->authenticate($request, m::mock(\Dingo\Api\Routing\Route::class));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testAuthenticatingFailsAndThrowsException()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andThrow(new JWTException('foo'));

        $this->provider->authenticate($request, m::mock(\Dingo\Api\Routing\Route::class));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock(\Dingo\Api\Routing\Route::class))->id);
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock(\Dingo\Api\Routing\Route::class))->id);
    }
}
