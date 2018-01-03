<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Mockery as m;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Tests\Stubs\AuthorizationProviderStub;

class AuthorizationTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testExceptionThrownWhenAuthorizationHeaderIsInvalid()
    {
        $request = Request::create('GET', '/');

        (new AuthorizationProviderStub)->authenticate($request, m::mock(Route::class));
    }
}
