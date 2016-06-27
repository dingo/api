<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Mockery as m;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Auth\Provider\JWT;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class JWTTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->auth = m::mock(JWTAuth::class);
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
        $this->provider->authenticate($request, m::mock(Route::class));
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

        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertEquals(1, $this->provider->authenticate($request, m::mock(Route::class))->id);
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertEquals(1, $this->provider->authenticate($request, m::mock(Route::class))->id);
    }
}
