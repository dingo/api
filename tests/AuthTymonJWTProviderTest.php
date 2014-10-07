<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Dingo\Api\Auth\TymonJWTProvider;
use Tymon\JWTAuth\Exceptions\JWTAuthException;

class AuthTymonJWTProviderTest extends PHPUnit_Framework_TestCase
{
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
        $provider = new TymonJWTProvider($this->getAuthMock());
        $provider->authenticate($request, new Route('/foo', 'GET', []));
    }

    /**
	 * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
    public function testAuthenticatingFailsAndThrowsException()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $provider = new TymonJWTProvider($resource = $this->getAuthMock());
        $resource->shouldReceive('login')->andThrow(new JWTAuthException('foo'));

        $provider->authenticate($request, new Route('/foo', 'GET', []));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $provider = new TymonJWTProvider($resource = $this->getAuthMock());
        $user = (object) ['id' => 1];

        $resource->shouldReceive('login')->andReturn($user);
        $resource->shouldReceive('getSubject')->andReturn(1);

        $this->assertEquals(1, $resource->getSubject());

        $this->assertEquals(1, $provider->authenticate($request, new Route('/foo', 'GET', []))->id);
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $provider = new TymonJWTProvider($resource = $this->getAuthMock());
        $user = (object) ['id' => 1];

        $resource->shouldReceive('login')->andReturn($user);
        $resource->shouldReceive('getSubject')->andReturn(1);

        $this->assertEquals(1, $resource->getSubject());

        $this->assertEquals(1, $provider->authenticate($request, new Route('/foo', 'GET', []))->id);
    }

    protected function getAuthMock()
    {
        return m::mock('Tymon\JWTAuth\JWTAuth');
    }
}
