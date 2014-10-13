<?php

namespace Dingo\Api\Tests\Auth;

use Mockery;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Auth\JWTProvider;
use Tymon\JWTAuth\Exceptions\JWTAuthException;
use PHPUnit_Framework_TestCase;

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
        
        $this->auth->shouldReceive('login')->andThrow(new JWTAuthException('foo'));

        $this->provider->authenticate($request, new Route('/foo', 'GET', []));
    }


    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $user = (object) ['id' => 1];

        $this->auth->shouldReceive('login')->andReturn($user);
        $this->auth->shouldReceive('getSubject')->andReturn(1);

        $this->assertEquals(1, $this->auth->getSubject());
        $this->assertEquals(1, $this->provider->authenticate($request, new Route('/foo', 'GET', []))->id);
    }


    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $user = (object) ['id' => 1];

        $this->auth->shouldReceive('login')->andReturn($user);
        $this->auth->shouldReceive('getSubject')->andReturn(1);

        $this->assertEquals(1, $this->auth->getSubject());
        $this->assertEquals(1, $this->provider->authenticate($request, new Route('/foo', 'GET', []))->id);
    }
}
