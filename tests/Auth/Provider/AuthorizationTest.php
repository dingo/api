<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Dingo\Api\Routing\Route;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\AuthorizationProviderStub;
use Illuminate\Http\Request;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthorizationTest extends BaseTestCase
{
    public function testExceptionThrownWhenAuthorizationHeaderIsInvalid()
    {
        $this->expectException(BadRequestHttpException::class);

        $request = Request::create('GET', '/');

        (new AuthorizationProviderStub)->authenticate($request, m::mock(Route::class));
    }
}
