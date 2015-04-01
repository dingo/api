<?php

namespace Dingo\Api\Tests\Auth;

use Mockery;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Auth\JWTProvider;
use PHPUnit_Framework_TestCase;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->auth = Mockery::mock('Tymon\JWTAuth\JWTAuth');
        $this->provider = new JWTProvider($this->auth);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testValidatingAuthorizationHeaderFailsAndThrowsException()
    {
        $request = Request::create('foo', 'GET');
        $this->provider->authenticate($request, new Route('/foo', 'GET', []));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testAuthenticatingFailsAndThrowsException()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('authenticate')->with('foo')->andThrow(new JWTException('foo'));

        $this->provider->authenticate($request, new Route('/foo', 'GET', []));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('authenticate')->with('foo')->andReturn((object) ['id' => 1]);

        $this->assertEquals(1, $this->provider->authenticate($request, new Route('/foo', 'GET', []))->id);
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $this->auth->shouldReceive('authenticate')->with('foo')->andReturn((object) ['id' => 1]);

        $this->assertEquals(1, $this->provider->authenticate($request, new Route('/foo', 'GET', []))->id);
    }
}
