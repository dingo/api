<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Auth\Provider\Basic;

class BasicTest extends TestCase
{
    protected $auth;
    protected $provider;

    public function setUp()
    {
        $this->auth = m::mock('Illuminate\Auth\AuthManager');
        $this->provider = new Basic($this->auth);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function testInvalidBasicCredentialsThrowsException()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('onceBasic')->once()->with('email')->andReturn(new Response('', 401));

        $this->provider->authenticate($request, m::mock(\Dingo\Api\Routing\Route::class));
    }

    public function testValidCredentialsReturnsUser()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('onceBasic')->once()->with('email')->andReturn(null);
        $this->auth->shouldReceive('user')->once()->andReturn('foo');

        $this->assertSame('foo', $this->provider->authenticate($request, m::mock(\Dingo\Api\Routing\Route::class)));
    }
}
