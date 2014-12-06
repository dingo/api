<?php

namespace Dingo\Api\Tests\Auth;

use Mockery;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Response;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Auth\BasicProvider;

class BasicProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->auth = Mockery::mock('Illuminate\Auth\AuthManager');
        $this->provider = new BasicProvider($this->auth);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testInvalidBasicCredentialsThrowsException()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('onceBasic')->once()->with('email', $request)->andReturn(new Response('', 401));

        $this->provider->authenticate($request, new Route(['GET'], '/', []));
    }

    public function testValidCredentialsReturnsUser()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('onceBasic')->once()->with('email', $request)->andReturn(null);
        $this->auth->shouldReceive('user')->once()->andReturn('foo');

        $this->assertEquals('foo', $this->provider->authenticate($request, new Route(['GET'], '/', [])));
    }
}
